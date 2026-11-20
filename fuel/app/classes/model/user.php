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
	 * IDでユーザーのメールアドレスを取得する
	 *
	 * 画面のヘッダーに、どのアカウントでログインしているかを出すため。
	 *
	 * @param  int $user_id
	 * @return string|null  見つからなければnull
	 */
	public static function find_email($user_id)
	{
		$row = \DB::select('email')
			->from('users')
			->where('id', '=', $user_id)
			->execute()
			->current();

		return $row ? $row['email'] : null;
	}

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
	 * 呼び出し元で exists() を確かめていても、その直後に同じメールアドレスの登録が割り込むことがある
	 * （登録ボタンのダブルクリックなど）。そのときは UNIQUE 制約で INSERT が拒否されるので、
	 * 500エラーにせず null を返し、呼び出し元が「登録済み」として扱えるようにする。
	 *
	 * @param  string $email
	 * @param  string $password 平文のパスワード
	 * @return int|null  登録したユーザーのID。メールアドレスが登録済みならnull
	 * @throws \Database_Exception  重複以外のDBエラー
	 */
	public static function create($email, $password)
	{
		$row = array(
			'email'         => static::normalize_email($email),
			'password_hash' => password_hash($password, PASSWORD_DEFAULT),
			'created_at'    => \DB::expr('NOW()'),
		);

		// catch で受け止めたいのは INSERT の重複だけなので、try は INSERT だけを囲み、
		// どの処理の失敗を扱っているのかを読んで分かるようにする
		try
		{
			list($id) = \DB::insert('users')->set($row)->execute();
		}
		catch (\Database_Exception $e)
		{
			// 1062 は users のどの UNIQUE 制約の違反でも出るので、メールアドレスが本当に登録済みかを確かめる。
			// エラー文の制約名では見分けない（MySQL 8.0 では 'users.users_email_unique' のように形が変わり、
			// 入力されたメールアドレスも文に入るため）
			if (static::is_duplicate_entry($e) and static::exists($email))
			{
				return null;
			}

			throw $e;
		}

		return (int) $id;
	}

	/**
	 * UNIQUE 制約の違反（MySQL のエラー番号 1062）か
	 *
	 * 接続に mysqli ドライバを使っているので、getCode() に MySQL のエラー番号が入る。
	 * PDO ドライバに変えると SQLSTATE（23000）が入り、ここでは見分けられなくなる。
	 *
	 * @param  \Database_Exception $e
	 * @return bool
	 */
	protected static function is_duplicate_entry(\Database_Exception $e)
	{
		return (int) $e->getCode() === 1062;
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
