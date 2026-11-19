<?php

/**
 * Model_Card のテスト。
 * 各テストはトランザクションの中で動かし、終わったらロールバックしてテーブルを元に戻す。
 *
 * @group App
 */
class Test_Model_Card extends TestCase
{
	/** @var int */
	protected $user_id;

	/** @var int */
	protected $other_user_id;

	/** @var int */
	protected $board_id;

	protected function setUp()
	{
		parent::setUp();
		DB::start_transaction();

		$this->user_id = Model_User::create('taro@example.com', 'correct-password');
		$this->other_user_id = Model_User::create('hanako@example.com', 'correct-password');
		$this->assertNotNull($this->user_id, 'テスト用のユーザーを作れなかった（同じメールアドレスが残っている？）');
		$this->assertNotNull($this->other_user_id, 'テスト用のユーザーを作れなかった（同じメールアドレスが残っている？）');
		$this->board_id = Model_Board::create($this->user_id, 'エンジニア採用');
	}

	protected function tearDown()
	{
		DB::rollback_transaction();
		parent::tearDown();
	}

	public function test_作成したカードを取得できる()
	{
		$id = Model_Card::create($this->board_id, $this->user_id, 2, '山田太郎', '経験3年');

		$card = Model_Card::find($id, $this->user_id);

		$this->assertSame($this->board_id, (int) $card['board_id']);
		$this->assertSame(2, (int) $card['status_id']);
		$this->assertSame('山田太郎', $card['title']);
		$this->assertSame('経験3年', $card['memo']);
	}

	public function test_作成すると列の一番下に入る()
	{
		$first = Model_Card::create($this->board_id, $this->user_id, 1, '1人目', null);
		$second = Model_Card::create($this->board_id, $this->user_id, 1, '2人目', null);
		$other_column = Model_Card::create($this->board_id, $this->user_id, 2, '別の列', null);

		$this->assertSame(0, (int) Model_Card::find($first, $this->user_id)['sort_order']);
		$this->assertSame(1, (int) Model_Card::find($second, $this->user_id)['sort_order']);
		$this->assertSame(0, (int) Model_Card::find($other_column, $this->user_id)['sort_order']);
	}

	public function test_作成に失敗すると元の例外を投げる()
	{
		$this->expectException(Database_Exception::class);

		// statuses にない列なので、INSERT が外部キー違反になる
		Model_Card::create($this->board_id, $this->user_id, 99, '応募者', null);
	}

	public function test_他人のボードには作成できない()
	{
		$others_board = Model_Board::create($this->other_user_id, '他人のボード');

		$this->assertNull(Model_Card::create($others_board, $this->user_id, 1, '山田太郎', null));
		$this->assertSame(array(), Model_Card::find_by_board($others_board, $this->other_user_id));
	}

	public function test_一覧は列ごとに上から順に並ぶ()
	{
		$b1 = Model_Card::create($this->board_id, $this->user_id, 2, 'B1', null);
		$a1 = Model_Card::create($this->board_id, $this->user_id, 1, 'A1', null);
		$a2 = Model_Card::create($this->board_id, $this->user_id, 1, 'A2', null);

		$ids = array_map('intval', array_column(Model_Card::find_by_board($this->board_id, $this->user_id), 'id'));

		$this->assertSame(array($a1, $a2, $b1), $ids);
	}

	public function test_他人のボードのカードは一覧に出ない()
	{
		Model_Card::create($this->board_id, $this->user_id, 1, '山田太郎', null);

		$this->assertSame(array(), Model_Card::find_by_board($this->board_id, $this->other_user_id));
	}

	public function test_他人のカードは取得できない()
	{
		$id = Model_Card::create($this->board_id, $this->user_id, 1, '山田太郎', null);

		$this->assertNull(Model_Card::find($id, $this->other_user_id));
	}

	public function test_存在しないカードは取得できない()
	{
		$this->assertNull(Model_Card::find(999999, $this->user_id));
	}

	public function test_編集すると応募者名とメモと更新日時が変わる()
	{
		$id = Model_Card::create($this->board_id, $this->user_id, 1, '古い名前', '古いメモ');
		$this->set_card_updated_at($id, '2026-01-01 00:00:00');

		Model_Card::update($id, $this->user_id, '新しい名前', '新しいメモ');

		$card = Model_Card::find($id, $this->user_id);
		$this->assertSame('新しい名前', $card['title']);
		$this->assertSame('新しいメモ', $card['memo']);
		$this->assertNotSame('2026-01-01 00:00:00', $card['updated_at']);
	}

	public function test_他人のカードは編集できない()
	{
		$id = Model_Card::create($this->board_id, $this->user_id, 1, '山田太郎', null);

		Model_Card::update($id, $this->other_user_id, '書き換え', null);

		$this->assertSame('山田太郎', Model_Card::find($id, $this->user_id)['title']);
	}

	public function test_カードを削除できる()
	{
		$id = Model_Card::create($this->board_id, $this->user_id, 1, '山田太郎', null);

		Model_Card::delete($id, $this->user_id);

		$this->assertNull(Model_Card::find($id, $this->user_id));
	}

	public function test_他人のカードは削除されない()
	{
		$id = Model_Card::create($this->board_id, $this->user_id, 1, '山田太郎', null);

		Model_Card::delete($id, $this->other_user_id);

		$this->assertNotNull(Model_Card::find($id, $this->user_id));
	}

	/**
	 * @dataProvider provide_operations
	 */
	public function test_カードを操作するとボードの更新日時が変わる($operation)
	{
		$id = Model_Card::create($this->board_id, $this->user_id, 1, '山田太郎', null);
		$this->set_board_updated_at('2026-01-01 00:00:00');

		switch ($operation)
		{
			case 'create':
				Model_Card::create($this->board_id, $this->user_id, 1, '2人目', null);
				break;
			case 'update':
				Model_Card::update($id, $this->user_id, '山田花子', null);
				break;
			case 'delete':
				Model_Card::delete($id, $this->user_id);
				break;
		}

		$board = Model_Board::find_by_owner($this->user_id)[0];
		$this->assertNotSame('2026-01-01 00:00:00', $board['updated_at']);
	}

	public function provide_operations()
	{
		return array(
			'作成' => array('create'),
			'編集' => array('update'),
			'削除' => array('delete'),
		);
	}

	/**
	 * 更新日時が変わったことを確かめるため、過去の日時に書き換える（操作は現在時刻を入れるので、同じ秒だと差が出ない）
	 */
	protected function set_card_updated_at($card_id, $datetime)
	{
		DB::update('cards')->value('updated_at', $datetime)->where('id', '=', $card_id)->execute();
	}

	/**
	 * set_card_updated_at() と同じ理由で、ボードの更新日時を書き換える
	 */
	protected function set_board_updated_at($datetime)
	{
		DB::update('boards')->value('updated_at', $datetime)->where('id', '=', $this->board_id)->execute();
	}
}
