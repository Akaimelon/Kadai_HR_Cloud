<?php
/**
 * before() の中で画面の処理を止め、別のページへ転送するための例外。
 *
 * Response::redirect() は中で exit するため、PHPUnit から呼ぶとテストごと終わってしまう。
 * 例外なら、テストで受け止めて転送先を確かめられる。
 * HttpException を継承した例外は、FuelPHPのエラーハンドラが response() の内容をそのまま返す（HttpJsonException と同じ）。
 */
class HttpRedirectException extends \HttpException
{
	/**
	 * @var string
	 */
	protected $uri;

	/**
	 * @param string $uri  転送先（'auth/login' のようなアプリの中のパス）
	 */
	public function __construct($uri)
	{
		parent::__construct('Redirect to '.$uri);
		$this->uri = $uri;
	}

	/**
	 * @return string
	 */
	public function uri()
	{
		return $this->uri;
	}

	public function response()
	{
		return \Response::forge('', 302, array('Location' => \Uri::create($this->uri)));
	}
}
