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

	public function test_登録済みかの確認も失敗したら元の例外を投げる()
	{
		Model_User::create('taro@example.com', 'correct-password');

		$e = $this->catch_with_failing_select(function () {
			Model_User::create('taro@example.com', 'other-password');
		});

		// 確認の例外に置き換わると、INSERT が重複で拒否されたという元の原因がログに残らない
		$this->assertInstanceOf(Database_Exception::class, $e);
		$this->assertSame(1062, (int) $e->getCode());
	}

	/**
	 * SELECT だけが例外を出す接続に差し替えて $fn を実行し、投げられた例外を返す
	 *
	 * INSERT の後に接続が切れたときのように、登録済みかの確認だけが失敗する状態は、本物の DB では作れないため。
	 * 差し替え方は Model_Card のテストの catch_with_failing_rollback() と同じ
	 */
	protected function catch_with_failing_select(callable $fn)
	{
		$name = Config::get('db.active');
		$real = Database_Connection::$instances[$name];
		$fake = new class($real) extends Database_MySQLi_Connection {
			public function __construct(Database_MySQLi_Connection $real)
			{
				// 同じ接続とトランザクションの深さを引き継ぎ、SELECT 以外は本物と同じに動かす
				foreach (get_object_vars($real) as $key => $value)
				{
					$this->$key = $value;
				}
			}

			public function query($type, $sql, $as_object)
			{
				if ($type === DB::SELECT)
				{
					throw new RuntimeException('SELECT に失敗した（テスト用）');
				}

				return parent::query($type, $sql, $as_object);
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
			// tearDown のロールバックは本物の接続で行い、テストのデータを戻す
			$fake->forget_connection();
			Database_Connection::$instances[$name] = $real;
		}

		$this->fail('例外が投げられなかった');
	}

	public function test_メールアドレスは大文字小文字と前後の空白を区別しない()
	{
		Model_User::create(' Taro@Example.com ', 'correct-password');

		$this->assertTrue(Model_User::exists('taro@example.com'));
		$this->assertNotNull(Model_User::authenticate('TARO@EXAMPLE.COM', 'correct-password'));
	}
}
