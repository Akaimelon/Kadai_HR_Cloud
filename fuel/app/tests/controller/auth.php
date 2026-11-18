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
		Guard::login(Model_User::create('taro@example.com', 'correct-password'));

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
		Guard::login(Model_User::create('taro@example.com', 'correct-password'));

		$response = Request::forge('boards/index')->execute()->response();

		$this->assertSame(200, $response->status);
	}

	public function test_転送の例外は302とLocationヘッダーを返す()
	{
		$e = new HttpRedirectException('auth/login');
		$response = $e->response();

		$this->assertSame(302, $response->status);
		$this->assertStringEndsWith('auth/login', $response->get_header('Location'));
	}
}
