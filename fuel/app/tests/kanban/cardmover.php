<?php

/**
 * Kanban\CardMover のテスト。
 * 各テストはトランザクションの中で動かし、終わったらロールバックしてテーブルを元に戻す。
 *
 * @group App
 */
class Test_Kanban_CardMover extends TestCase
{
	/** @var int */
	protected $user_id;

	/** @var int */
	protected $board_id;

	protected function setUp()
	{
		parent::setUp();
		DB::start_transaction();

		$this->user_id = Model_User::create('taro@example.com', 'correct-password');
		$this->assertNotNull($this->user_id, 'テスト用のユーザーを作れなかった（同じメールアドレスが残っている？）');
		$this->board_id = Model_Board::create($this->user_id, 'エンジニア採用');
	}

	protected function tearDown()
	{
		DB::rollback_transaction();
		parent::tearDown();
	}

	public function test_列内で下に移動できる()
	{
		list($a, $b, $c) = $this->create_cards(1, array('A', 'B', 'C'));

		$this->move($a, 1, 2);

		$this->assertColumn(1, array($b, $c, $a));
	}

	public function test_列内で上に移動できる()
	{
		list($a, $b, $c) = $this->create_cards(1, array('A', 'B', 'C'));

		$this->move($c, 1, 0);

		$this->assertColumn(1, array($c, $a, $b));
	}

	public function test_別の列に移動すると移動元が詰まり移動先に入る()
	{
		list($a, $b, $c) = $this->create_cards(1, array('A', 'B', 'C'));
		list($x, $y) = $this->create_cards(2, array('X', 'Y'));

		$this->move($b, 2, 1);

		$this->assertColumn(1, array($a, $c));
		$this->assertColumn(2, array($x, $b, $y));
	}

	public function test_空の列に移動できる()
	{
		list($a, $b) = $this->create_cards(1, array('A', 'B'));

		$this->move($a, 3, 0);

		$this->assertColumn(1, array($b));
		$this->assertColumn(3, array($a));
	}

	/**
	 * @dataProvider provide_out_of_range
	 */
	public function test_範囲外の位置は端に丸める($sort_order, $expected_index)
	{
		list($x, $y) = $this->create_cards(2, array('X', 'Y'));
		list($a) = $this->create_cards(1, array('A'));

		$this->move($a, 2, $sort_order);

		$expected = array($x, $y);
		array_splice($expected, $expected_index, 0, array($a));
		$this->assertColumn(2, $expected);
	}

	public function provide_out_of_range()
	{
		return array(
			'枚数より大きい' => array(99, 2),
			'マイナス'       => array(-1, 0),
		);
	}

	public function test_削除でできた欠番も連番に振り直す()
	{
		list($a, $b, $c, $d) = $this->create_cards(1, array('A', 'B', 'C', 'D'));
		Model_Card::delete($b, $this->user_id);

		$this->move($d, 1, 0);

		$this->assertColumn(1, array($d, $a, $c));
	}

	public function test_ロックを待つ間に別の列へ動いていても実際に抜けた列を詰める()
	{
		list($a) = $this->create_cards(1, array('A'));
		list($x, $y) = $this->create_cards(2, array('X', 'Y'));
		// 同じカードの移動が2つ重なり、先の移動（1列目→2列目の先頭）が終わってから、
		// 後の移動がロックを取った状況。後の移動には、先の移動の前に読んだカードが渡る
		$stale = Model_Card::find($a, $this->user_id);
		$this->move($a, 2, 0);

		Kanban\CardMover::move($stale, 3, 0);

		$this->assertColumn(2, array($x, $y));
		$this->assertColumn(3, array($a));
	}

	public function test_ロックを待つ間にカードが削除されていたら何も変えずfalse()
	{
		list($a, $b) = $this->create_cards(1, array('A', 'B'));
		list($x) = $this->create_cards(2, array('X'));
		$stale = Model_Card::find($a, $this->user_id);
		Model_Card::delete($a, $this->user_id);

		$this->assertFalse(Kanban\CardMover::move($stale, 2, 0));

		// 消えたカードを入れて振り直すと、X が 1 になって欠番ができる
		$this->assertColumn(2, array($x));
	}

	public function test_ロックを待つ間にボードが削除されていたらfalse()
	{
		list($a) = $this->create_cards(1, array('A'));
		$stale = Model_Card::find($a, $this->user_id);
		Model_Board::delete($this->board_id, $this->user_id);

		$this->assertFalse(Kanban\CardMover::move($stale, 2, 0));
	}

	public function test_動かしたカードとボードの更新日時だけが変わる()
	{
		list($a, $b) = $this->create_cards(1, array('A', 'B'));
		DB::update('cards')->value('updated_at', '2026-01-01 00:00:00')->where('board_id', '=', $this->board_id)->execute();
		DB::update('boards')->value('updated_at', '2026-01-01 00:00:00')->where('id', '=', $this->board_id)->execute();

		$this->move($b, 1, 0);

		$this->assertNotSame('2026-01-01 00:00:00', Model_Card::find($b, $this->user_id)['updated_at']);
		// 並び順がずれただけのカードは、選考ステージも内容も変わっていないので更新日時を変えない
		$this->assertSame('2026-01-01 00:00:00', Model_Card::find($a, $this->user_id)['updated_at']);
		$this->assertNotSame('2026-01-01 00:00:00', Model_Board::find_by_owner($this->user_id)[0]['updated_at']);
	}

	public function test_途中で失敗すると元の例外を投げて並び順を戻す()
	{
		list($a, $b, $c) = $this->create_cards(1, array('A', 'B', 'C'));

		try
		{
			// statuses にない列は、振り直しの後の UPDATE で外部キー違反になる。
			// 戻らないと、移動元の列の振り直しで C の sort_order が 1 になったまま残る
			$this->move($b, 99, 0);
			$this->fail('例外が投げられなかった');
		}
		catch (Database_Exception $e)
		{
		}

		$this->assertColumn(1, array($a, $b, $c));
	}

	public function test_ロールバックも失敗したら元の例外を投げる()
	{
		list($a, $b) = $this->create_cards(1, array('A', 'B'));

		$e = $this->catch_with_failing_rollback(function () use ($b) {
			$this->move($b, 99, 0);
		});

		// ロールバックの例外に置き換わると、外部キー違反という元の原因がログに残らない
		$this->assertInstanceOf(Database_Exception::class, $e);
	}

	/**
	 * ロールバックだけが例外を出す接続に差し替えて $fn を実行し、投げられた例外を返す
	 *
	 * 接続が切れたときのようにロールバックが失敗する状態は、本物の DB では1つの接続の中で作れないため。
	 * Model_Card のテストにも同じものがある（テストのファイルどうしでクラスを共有できないため）
	 */
	protected function catch_with_failing_rollback(callable $fn)
	{
		$name = Config::get('db.active');
		$real = Database_Connection::$instances[$name];
		$fake = new class($real) extends Database_MySQLi_Connection {
			public function __construct(Database_MySQLi_Connection $real)
			{
				// 同じ PDO とトランザクションの深さを引き継ぎ、ロールバック以外は本物と同じに動かす
				foreach (get_object_vars($real) as $key => $value)
				{
					$this->$key = $value;
				}
			}

			public function rollback_transaction($rollback_all = true)
			{
				throw new RuntimeException('ロールバックに失敗した（テスト用）');
			}

			public function forget_connection()
			{
				// 破棄されるときの disconnect() が、本物と共有している接続を close() しないため
				$this->_connection = null;
			}
		};
		Database_Connection::$instances[$name] = $fake;

		try
		{
			$fn();
		}
		catch (Throwable $e)
		{
			return $e;
		}
		finally
		{
			// tearDown のロールバックは本物の接続で行い、外側のトランザクションごとテストのデータを戻す
			$fake->forget_connection();
			Database_Connection::$instances[$name] = $real;
		}

		$this->fail('例外が投げられなかった');
	}

	/**
	 * @return int[]  作ったカードのID（列の上から順）
	 */
	protected function create_cards($status_id, array $titles)
	{
		$ids = array();
		foreach ($titles as $title)
		{
			$ids[] = Model_Card::create($this->board_id, $this->user_id, $status_id, $title, null);
		}
		return $ids;
	}

	protected function move($card_id, $status_id, $sort_order)
	{
		Kanban\CardMover::move(Model_Card::find($card_id, $this->user_id), $status_id, $sort_order);
	}

	/**
	 * 列のカードが期待どおりの順に並び、sort_order が0からの連番になっているか確かめる
	 */
	protected function assertColumn($status_id, array $expected_ids)
	{
		$cards = array_values(array_filter(
			Model_Card::find_by_board($this->board_id, $this->user_id),
			function ($card) use ($status_id) { return (int) $card['status_id'] === $status_id; }
		));

		$this->assertSame($expected_ids, array_map('intval', array_column($cards, 'id')));
		$this->assertSame(range(0, count($expected_ids) - 1), array_map('intval', array_column($cards, 'sort_order')));
	}
}
