<?php
/**
 * 採用担当者（users テーブル）のモデル。
 *
 * パスワードは password_hash() で作ったハッシュだけを保存し、平文は保存もログ出力もしない。
 * メールアドレスは大文字・小文字の違いで別人扱いにならないよう、小文字に揃えて保存・検索する。
 */
class Model_User
{
	/**
	 * メールアドレスでユーザーを1件取得する
	 *
	 * @param  string $email
	 * @return array|null  id・email・password_hash・created_at。見つからなければnull
	 */
	public static function find_by_email($email)
	{
		$row = \DB::select('id', 'email', 'password_hash', 'created_at')
			->from('users')
			->where('email', '=', static::normalize_email($email))
			->execute()
			->current();

		return $row ?: null;
	}

	/**
	 * そのメールアドレスが登録済みか
	 *
	 * @param  string $email
	 * @return bool
	 */
	public static function exists($email)
	{
		return static::find_by_email($email) !== null;
	}

	/**
	 * ユーザーを登録する
	 *
	 * @param  string $email
	 * @param  string $password 平文のパスワード
	 * @return int  登録したユーザーのID
	 */
	public static function create($email, $password)
	{
		list($id) = \DB::insert('users')
			->set(array(
				'email'         => static::normalize_email($email),
				'password_hash' => password_hash($password, PASSWORD_DEFAULT),
				'created_at'    => date('Y-m-d H:i:s'),
			))
			->execute();

		return (int) $id;
	}

	/**
	 * メールアドレスとパスワードが正しければユーザーを返す
	 *
	 * 未登録とパスワード違いを呼び出し元で区別できないよう、どちらもnullを返す。
	 *
	 * @param  string $email
	 * @param  string $password 平文のパスワード
	 * @return array|null
	 */
	public static function authenticate($email, $password)
	{
		$user = static::find_by_email($email);

		if ($user === null or ! password_verify($password, $user['password_hash']))
		{
			return null;
		}

		return $user;
	}

	/**
	 * 前後の空白を除いて小文字に揃える
	 *
	 * @param  string $email
	 * @return string
	 */
	protected static function normalize_email($email)
	{
		return mb_strtolower(trim($email));
	}
}
