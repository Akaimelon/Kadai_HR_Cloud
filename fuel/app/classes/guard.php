<?php
/**
 * ログイン状態とCSRFトークンの確認を1か所にまとめたクラス。
 *
 * 画面用の Controller_Base と API用の Controller_Api_Base の before() から呼ぶ。
 * 確認の中身はここだけに書き、確認に失敗したときの返し方（リダイレクトか401か）だけを
 * それぞれの基底コントローラが決める。
 */
class Guard
{
	const OK            = 'ok';
	const NOT_LOGGED_IN = 'not_logged_in';
	const INVALID_TOKEN = 'invalid_token';

	/**
	 * リクエストを通してよいかを確認する
	 *
	 * @param  bool         $login_required  ログインが必要なアクションか
	 * @param  string|null  $token           リクエストで送られてきたCSRFトークン
	 * @return string  Guard::OK / Guard::NOT_LOGGED_IN / Guard::INVALID_TOKEN
	 */
	public static function check($login_required, $token)
	{
		if ($login_required and static::user_id() === null)
		{
			return static::NOT_LOGGED_IN;
		}

		if (static::changes_data() and ! static::is_valid_token($token))
		{
			return static::INVALID_TOKEN;
		}

		return static::OK;
	}

	/**
	 * ログイン中のユーザーのID。未ログインならnull
	 *
	 * @return int|null
	 */
	public static function user_id()
	{
		$user_id = \Session::get('user_id');

		return $user_id === null ? null : (int) $user_id;
	}

	/**
	 * ログイン状態にする。セッション固定化を防ぐため、ログイン前のセッションを捨てて新しく作る。
	 *
	 * Session::rotate() は使わない。fileドライバの rotate() は古いIDのファイルに
	 * 「新しいIDへの転送先」を残すため、ログイン前のセッションIDでもログイン後のセッションに入れてしまう。
	 *
	 * @param int $user_id
	 */
	public static function login($user_id)
	{
		\Session::destroy();
		\Session::create();
		\Session::set('user_id', (int) $user_id);
	}

	public static function logout()
	{
		\Session::destroy();
	}

	/**
	 * データを変えるメソッド（POST・PUT・DELETEなど）か。GETなどはCSRFトークンを確認しない
	 *
	 * @return bool
	 */
	protected static function changes_data()
	{
		return ! in_array(\Input::method(), array('GET', 'HEAD', 'OPTIONS'), true);
	}

	/**
	 * 送られてきたトークンが、Cookieに入っているトークンと一致するか。
	 *
	 * Security::check_token() は確認のたびにトークンを作り直すため、画面を開いたまま
	 * APIを2回呼ぶと2回目が失敗する。そのため Security::fetch_token() と直接比べる。
	 * hash_equals() は文字列の比較にかかる時間から値を推測されないようにするため。
	 *
	 * @param  string|null $token
	 * @return bool
	 */
	protected static function is_valid_token($token)
	{
		return is_string($token) and $token !== '' and hash_equals(\Security::fetch_token(), $token);
	}
}
