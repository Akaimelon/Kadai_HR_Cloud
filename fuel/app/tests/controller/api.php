<?php

/**
 * API（JSON）に、画面が送らない形の本文が届いたときのテスト。
 * 配列などを文字列として扱うと Notice・Warning になり500になっていたので、422になることを確かめる。
 *
 * @group App
 */
class Test_Controller_Api extends TestCase
{
	/**
	 * @var int
	 */
	protected $board_id;

	/**
	 * @var int
	 */
	protected $card_id;

	protected function setUp()
	{
		parent::setUp();
		DB::start_transaction();
		Session::destroy();

		$user_id = Model_User::create('taro@example.com', 'correct-password');
		$this->assertNotNull($user_id, 'テスト用のユーザーを作れなかった（同じメールアドレスが残っている？）');
		Guard::login($user_id);

		$this->board_id = Model_Board::create($user_id, 'ボード');
		$this->assertNotNull($this->board_id);
		$this->card_id = Model_Card::create($this->board_id, $user_id, 1, '応募者', null);
		$this->assertNotNull($this->card_id);

		$_SERVER['HTTP_X_CSRF_TOKEN'] = Security::fetch_token();
	}

	protected function tearDown()
	{
		unset($_SERVER['HTTP_X_CSRF_TOKEN']);
		Session::destroy();
		DB::rollback_transaction();
		parent::tearDown();
	}

	public function test_ボード名が文字列なら作成できる()
	{
		$this->assertSame(201, $this->send_json('POST', 'api/boards', '{"title":"新卒採用"}'));
	}

	public function test_ボード名が配列なら422()
	{
		$this->assertSame(422, $this->send_json('POST', 'api/boards', '{"title":["x"]}'));
	}

	public function test_ボード名が真偽値なら422()
	{
		$this->assertSame(422, $this->send_json('POST', 'api/boards', '{"title":true}'));
	}

	public function test_本文がオブジェクトでないJSONなら422()
	{
		$this->assertSame(422, $this->send_json('POST', 'api/boards', '"abc"'));
	}

	public function test_ボード名の変更で名前がオブジェクトなら422()
	{
		$this->assertSame(422, $this->send_json('PUT', 'api/boards/'.$this->board_id, '{"title":{"a":"x"}}'));
	}

	public function test_カード作成でメモが配列なら422()
	{
		$this->assertSame(422, $this->send_json('POST', 'api/boards/'.$this->board_id.'/cards', '{"title":"応募者","memo":["x"],"status_id":1}'));
	}

	public function test_カード編集で応募者名が配列なら422()
	{
		$this->assertSame(422, $this->send_json('PUT', 'api/cards/'.$this->card_id, '{"title":["x"]}'));
	}

	public function test_カード移動で位置が配列なら422()
	{
		$this->assertSame(422, $this->send_json('PUT', 'api/cards/'.$this->card_id.'/move', '{"status_id":1,"sort_order":[0]}'));
	}

	/**
	 * カードを操作するとボードの更新日時も変わる。画面の見出しにブラウザの時計ではなくその値を出せるよう、
	 * どの操作でもボードの updated_at を返すことを確かめる
	 */
	public function test_カードの作成・編集・移動・削除はボードの更新日時を返す()
	{
		$requests = array(
			array('POST', 'api/boards/'.$this->board_id.'/cards', '{"title":"応募者","memo":"","status_id":1}', 201),
			array('PUT', 'api/cards/'.$this->card_id, '{"title":"応募者","memo":""}', 200),
			array('PUT', 'api/cards/'.$this->card_id.'/move', '{"status_id":2,"sort_order":0}', 200),
			array('DELETE', 'api/cards/'.$this->card_id, '', 200),
		);

		foreach ($requests as $request)
		{
			list($method, $uri, $body, $status) = $request;
			// 同じ秒の中では値が変わらず、更新したかを見分けられないため、先に古い日時にしておく
			DB::update('boards')->value('updated_at', '2000-01-01 00:00:00')->where('id', '=', $this->board_id)->execute();

			$response = $this->send_json_response($method, $uri, $body);

			$this->assertSame($status, $response->status, $method.' '.$uri);
			$json = json_decode($response->body(), true);
			$board = Model_Board::find($this->board_id, Guard::user_id());
			$this->assertNotSame('2000-01-01 00:00:00', $board['updated_at'], $method.' '.$uri);
			$this->assertSame($board['updated_at'], $json['board']['updated_at'], $method.' '.$uri);
		}
	}

	/**
	 * @param  string $method
	 * @param  string $uri
	 * @param  string $body  JSON の本文
	 * @return int  HTTPステータスコード
	 */
	protected function send_json($method, $uri, $body)
	{
		return $this->send_json_response($method, $uri, $body)->status;
	}

	/**
	 * @param  string $method
	 * @param  string $uri
	 * @param  string $body  JSON の本文
	 * @return Response
	 */
	protected function send_json_response($method, $uri, $body)
	{
		static::forget_request_state($body);

		try
		{
			return Request::forge($uri, true, $method)->execute()->response();
		}
		catch (HttpJsonException $e)
		{
			return $e->response();
		}
		finally
		{
			static::forget_request_state(null);
		}
	}

	/**
	 * テストは1つのプロセスで何度も送るため、Input が覚えている前の本文を消し、次の本文に差し替える。
	 * CLI では php://input に本文を入れられないので、Input が読んだあとの値を書き換える
	 *
	 * @param string|null $body
	 */
	protected static function forget_request_state($body)
	{
		$values = array('php_input' => $body, 'json' => null, 'input' => null);
		foreach ($values as $property => $value)
		{
			$prop = new ReflectionProperty('Input', $property);
			$prop->setAccessible(true);
			$prop->setValue($value);
		}

		$prop = new ReflectionProperty('Fieldset', '_instances');
		$prop->setAccessible(true);
		$prop->setValue(array());
	}
}
