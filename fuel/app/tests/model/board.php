<?php

/**
 * Model_Board のテスト。
 * 各テストはトランザクションの中で動かし、終わったらロールバックしてテーブルを元に戻す。
 *
 * @group App
 */
class Test_Model_Board extends TestCase
{
	/** @var int */
	protected $user_id;

	/** @var int */
	protected $other_user_id;

	protected function setUp()
	{
		parent::setUp();
		DB::start_transaction();

		$this->user_id = Model_User::create('taro@example.com', 'correct-password');
		$this->other_user_id = Model_User::create('hanako@example.com', 'correct-password');
	}

	protected function tearDown()
	{
		DB::rollback_transaction();
		parent::tearDown();
	}

	public function test_作成したボードが一覧に出る()
	{
		$id = Model_Board::create($this->user_id, 'エンジニア採用');

		$boards = Model_Board::find_by_owner($this->user_id);

		$this->assertCount(1, $boards);
		$this->assertSame($id, (int) $boards[0]['id']);
		$this->assertSame('エンジニア採用', $boards[0]['title']);
	}

	public function test_他人のボードは一覧に出ない()
	{
		Model_Board::create($this->other_user_id, '他人のボード');

		$this->assertSame(array(), Model_Board::find_by_owner($this->user_id));
	}

	public function test_一覧は更新日時の新しい順に並ぶ()
	{
		$old_id = Model_Board::create($this->user_id, '古いボード');
		$new_id = Model_Board::create($this->user_id, '新しいボード');
		$this->set_updated_at($old_id, '2026-01-01 00:00:00');
		$this->set_updated_at($new_id, '2026-01-02 00:00:00');

		$ids = array_map('intval', array_column(Model_Board::find_by_owner($this->user_id), 'id'));

		$this->assertSame(array($new_id, $old_id), $ids);
	}

	public function test_一覧に応募の件数が入る()
	{
		$with_cards = Model_Board::create($this->user_id, '応募あり');
		$empty = Model_Board::create($this->user_id, '応募なし');
		$this->insert_card($with_cards);
		$this->insert_card($with_cards);

		$counts = array_column(Model_Board::find_by_owner($this->user_id), 'card_count', 'id');

		$this->assertSame(2, (int) $counts[$with_cards]);
		$this->assertSame(0, (int) $counts[$empty]);
	}

	public function test_自分のボードは操作できる()
	{
		$id = Model_Board::create($this->user_id, 'エンジニア採用');

		$this->assertTrue(Model_Board::can_access($id, $this->user_id));
	}

	public function test_他人のボードは操作できない()
	{
		$id = Model_Board::create($this->other_user_id, '他人のボード');

		$this->assertFalse(Model_Board::can_access($id, $this->user_id));
	}

	public function test_存在しないボードは操作できない()
	{
		$this->assertFalse(Model_Board::can_access(999999, $this->user_id));
	}

	public function test_名前を変えると更新日時も変わる()
	{
		$id = Model_Board::create($this->user_id, '古い名前');
		$this->set_updated_at($id, '2026-01-01 00:00:00');

		Model_Board::rename($id, $this->user_id, '新しい名前');

		$board = Model_Board::find_by_owner($this->user_id)[0];
		$this->assertSame('新しい名前', $board['title']);
		$this->assertNotSame('2026-01-01 00:00:00', $board['updated_at']);
	}

	public function test_他人のボードの名前は変わらない()
	{
		$id = Model_Board::create($this->other_user_id, '他人のボード');

		Model_Board::rename($id, $this->user_id, '書き換え');

		$board = Model_Board::find_by_owner($this->other_user_id)[0];
		$this->assertSame('他人のボード', $board['title']);
	}

	public function test_ボードを削除するとカードも消える()
	{
		$id = Model_Board::create($this->user_id, 'エンジニア採用');
		$this->insert_card($id);

		Model_Board::delete($id, $this->user_id);

		$this->assertSame(array(), Model_Board::find_by_owner($this->user_id));
		$cards = DB::select('id')->from('cards')->where('board_id', '=', $id)->execute();
		$this->assertCount(0, $cards);
	}

	public function test_他人のボードは削除されない()
	{
		$id = Model_Board::create($this->other_user_id, '他人のボード');

		Model_Board::delete($id, $this->user_id);

		$this->assertCount(1, Model_Board::find_by_owner($this->other_user_id));
	}

	/**
	 * 並び順を確かめるため、更新日時を直接書き換える（create() は現在時刻を入れるので差が出ない）
	 */
	protected function set_updated_at($board_id, $datetime)
	{
		DB::update('boards')->value('updated_at', $datetime)->where('id', '=', $board_id)->execute();
	}

	/**
	 * Model_Card がまだないので、カードは直接 INSERT する
	 */
	protected function insert_card($board_id)
	{
		$now = date('Y-m-d H:i:s');
		DB::insert('cards')
			->set(array(
				'board_id'   => $board_id,
				'title'      => '応募者',
				'created_at' => $now,
				'updated_at' => $now,
			))
			->execute();
	}
}
