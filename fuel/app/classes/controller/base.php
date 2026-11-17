<?php
/**
 * 画面用コントローラの基底クラス。
 *
 * before() でログインとCSRFトークンを確認する。確認の中身は Guard にまとめている。
 * 継承して使う専用のクラスなので abstract にしている。
 */
abstract class Controller_Base extends Controller_Template
{
	/**
	 * ログインしていなくても開けるアクション名。子クラスで上書きする。
	 * get_ / post_ を付けない名前で書く。
	 * ここに書いてもCSRFトークンの確認は外れない。
	 *
	 * @var array
	 */
	protected $public_actions = array();

	public function before()
	{
		parent::before();

		$login_required = ! in_array($this->request->action, $this->public_actions, true);
		$token = \Input::post(\Config::get('security.csrf_token_key', 'fuel_csrf_token'));

		switch (Guard::check($login_required, $token))
		{
			case Guard::NOT_LOGGED_IN:
				\Response::redirect('auth/login');
				break;

			case Guard::INVALID_TOKEN:
				throw new \HttpNoAccessException();
		}

		$this->template->user_email = Guard::user_email();
	}
}
