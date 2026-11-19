<?php

/**
 * 画面の転送（ログイン画面へ・ボード一覧へ）のテスト。
 * 転送を Response::redirect()（中で exit する）ではなく、例外か Response で返しているので、テストから確かめられる。
 *
 * @group App
 */
class Test_Controller_Auth extends TestCase
{
	protected function setUp()
	{
		parent::setUp();
		DB::start_transaction();
		Session::destroy();
	}

	protected function tearDown()
	{
		Session::destroy();
		DB::rollback_transaction();
		parent::tearDown();
	}

	public function test_未ログインで画面を開くとログイン画面へ転送する()
	{
		try
		{
			Request::forge('boards/index')->execute();
			$this->fail('転送されなかった');
		}
		catch (HttpRedirectException $e)
		{
			$this->assertSame('auth/login', $e->uri());
		}
	}

	public function test_ログイン中にログイン画面を開くとボード一覧へ転送する()
	{
		$user_id = Model_User::create('taro@example.com', 'correct-password');
		$this->assertNotNull($user_id, 'テスト用のユーザーを作れなかった（同じメールアドレスが残っている？）');
		Guard::login($user_id);

		try
		{
			Request::forge('auth/login')->execute();
			$this->fail('転送されなかった');
		}
		catch (HttpRedirectException $e)
		{
			$this->assertSame('boards', $e->uri());
		}
	}

	public function test_ログイン中ならボード一覧を開ける()
	{
		$user_id = Model_User::create('taro@example.com', 'correct-password');
		$this->assertNotNull($user_id, 'テスト用のユーザーを作れなかった（同じメールアドレスが残っている？）');
		Guard::login($user_id);

		$response = Request::forge('boards/index')->execute()->response();

		$this->assertSame(200, $response->status);
	}

	/**
	 * 登録ボタンのダブルクリックで、1回目の登録が済んだあとに2回目が届いた場合を再現する
	 */
	public function test_登録済みのメールアドレスでもパスワードが合えばログインしてボード一覧へ転送する()
	{
		$user_id = Model_User::create('taro@example.com', 'correct-password');
		$this->assertNotNull($user_id, 'テスト用のユーザーを作れなかった（同じメールアドレスが残っている？）');

		$response = $this->post_form('register', 'taro@example.com', 'correct-password');

		$this->assertSame(302, $response->status);
		$this->assertStringEndsWith('boards', $response->get_header('Location'));
		$this->assertSame($user_id, Guard::user_id());
	}

	public function test_登録済みのメールアドレスでパスワードが違うと登録済みのエラーを出す()
	{
		Model_User::create('taro@example.com', 'correct-password');

		$response = $this->post_form('register', 'taro@example.com', 'other-password');

		$this->assertSame(200, $response->status);
		$this->assertSame(
			array('このメールアドレスはすでに登録されています'),
			$response->body()->get('content')->get('errors')
		);
		$this->assertNull(Guard::user_id());
	}

	/**
	 * 画面のフォームからは送れないが、name="email[]" のように手で送ると値が配列で届く
	 *
	 * @dataProvider provide_array_inputs
	 */
	public function test_配列が送られたら500にせず入力エラーを出す($action, $email, $password, $email_in_form)
	{
		$response = $this->post_form($action, $email, $password);

		$this->assertSame(200, $response->status);
		$this->assertNotEmpty($response->body()->get('content')->get('errors'));
		$this->assertSame($email_in_form, $response->body()->get('content')->get('email'));
		$this->assertNull(Guard::user_id());
	}

	/**
	 * 入れ子の配列は、エラー文を作るところ（FuelPHP の Validation_Error）でも Notice になったので別に確かめる
	 */
	public function provide_array_inputs()
	{
		return array(
			'登録・メールアドレス'     => array('register', array('a'), 'correct-password', ''),
			'登録・入れ子の配列'       => array('register', array('a' => array('b' => '1')), array('x'), ''),
			'登録・パスワード'         => array('register', 'taro@example.com', array('x'), 'taro@example.com'),
			'ログイン・メールアドレス' => array('login', array('a'), 'correct-password', ''),
			'ログイン・パスワード'     => array('login', 'taro@example.com', array('x'), 'taro@example.com'),
		);
	}

	public function test_転送の例外は302とLocationヘッダーを返す()
	{
		$e = new HttpRedirectException('auth/login');
		$response = $e->response();

		$this->assertSame(302, $response->status);
		$this->assertStringEndsWith('auth/login', $response->get_header('Location'));
	}

	/**
	 * 登録・ログインのフォームの送信を、CSRFトークン付きのPOSTで再現する
	 *
	 * @param  string       $action   register か login
	 * @param  string|array $email
	 * @param  string|array $password
	 * @return Response
	 */
	protected function post_form($action, $email, $password)
	{
		$_POST = array(
			'email'    => $email,
			'password' => $password,
			Config::get('security.csrf_token_key', 'fuel_csrf_token') => Security::fetch_token(),
		);

		try
		{
			return Request::forge('auth/'.$action, true, 'POST')->execute()->response();
		}
		finally
		{
			$_POST = array();
			static::forget_request_state();
		}
	}

	/**
	 * 本番は1リクエスト1プロセスだが、テストは1つのプロセスで何度もPOSTするため、
	 * FuelPHP がプロセスの中に覚えている前のリクエストの状態を消す。
	 * Input は最初に読んだ POST の値を使い続け、Validation::forge() は同じ名前で2回呼ぶと例外になる
	 */
	protected static function forget_request_state()
	{
		foreach (array('Input' => 'input', 'Fieldset' => '_instances') as $class => $property)
		{
			$prop = new ReflectionProperty($class, $property);
			$prop->setAccessible(true);
			$prop->setValue($class === 'Input' ? null : array());
		}
	}
}
