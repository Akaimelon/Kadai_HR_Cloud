<?php

/**
 * Model_User のテスト。
 * 各テストはトランザクションの中で動かし、終わったらロールバックして users テーブルを元に戻す。
 *
 * @group App
 */
class Test_Model_User extends TestCase
{
	protected function setUp()
	{
		parent::setUp();
		DB::start_transaction();
	}

	protected function tearDown()
	{
		DB::rollback_transaction();
		parent::tearDown();
	}

	public function test_正しいパスワードなら認証が通る()
	{
		$id = Model_User::create('taro@example.com', 'correct-password');

		$user = Model_User::authenticate('taro@example.com', 'correct-password');

		$this->assertNotNull($user);
		$this->assertSame($id, (int) $user['id']);
	}

	public function test_パスワードが違うと認証が通らない()
	{
		Model_User::create('taro@example.com', 'correct-password');

		$this->assertNull(Model_User::authenticate('taro@example.com', 'wrong-password'));
	}

	public function test_未登録のメールアドレスでは認証が通らない()
	{
		$this->assertNull(Model_User::authenticate('nobody@example.com', 'correct-password'));
	}

	public function test_パスワードは平文で保存されない()
	{
		Model_User::create('taro@example.com', 'correct-password');

		$user = Model_User::find_by_email('taro@example.com');

		$this->assertNotSame('correct-password', $user['password_hash']);
		$this->assertTrue(password_verify('correct-password', $user['password_hash']));
	}

	/**
	 * exists() の確認をすり抜けて同じメールアドレスの INSERT が届いた場合（同時の登録）を、create() を2回呼んで再現する
	 */
	public function test_登録済みのメールアドレスで登録するとnullを返す()
	{
		Model_User::create('taro@example.com', 'correct-password');

		$this->assertNull(Model_User::create('Taro@Example.com', 'other-password'));
		$this->assertNotNull(Model_User::authenticate('taro@example.com', 'correct-password'));
	}

	/**
	 * メールアドレス以外の UNIQUE 制約の違反（同じ 1062）を「登録済み」として隠さないことを確かめる。
	 * users にはほかの UNIQUE 制約がないので、全行で同じ値になる列に一時的に UNIQUE を付けて起こす。
	 * DDL はトランザクションを暗黙にコミットするので、このテストだけトランザクションの外で動かし、自分で後片付けする。
	 */
	public function test_メールアドレス以外のUNIQUE制約の違反は投げ直す()
	{
		DB::rollback_transaction();
		DBUtil::add_fields('users', array('tmp_unique' => array('type' => 'tinyint', 'default' => 0)));
		DBUtil::create_index('users', 'tmp_unique', 'users_tmp_unique', 'UNIQUE');

		try
		{
			Model_User::create('taro@example.com', 'correct-password');

			$this->setExpectedException('Database_Exception');
			Model_User::create('hanako@example.com', 'correct-password');
		}
		finally
		{
			DB::delete('users')->where('email', 'in', array('taro@example.com', 'hanako@example.com'))->execute();
			DBUtil::drop_fields('users', 'tmp_unique');
			DB::start_transaction();
		}
	}

	public function test_メールアドレスは大文字小文字と前後の空白を区別しない()
	{
		Model_User::create(' Taro@Example.com ', 'correct-password');

		$this->assertTrue(Model_User::exists('taro@example.com'));
		$this->assertNotNull(Model_User::authenticate('TARO@EXAMPLE.COM', 'correct-password'));
	}
}
