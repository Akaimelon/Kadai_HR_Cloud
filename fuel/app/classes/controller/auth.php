<?php
/**
 * POSTのCSRFトークンは Controller_Base::before() が確認するので、ここでは確認しない。
 */
class Controller_Auth extends Controller_Base
{
	protected $public_actions = array('register', 'login');

	public function before()
	{
		parent::before();

		if (in_array($this->request->action, $this->public_actions, true) and Guard::user_id() !== null)
		{
			throw new HttpRedirectException('boards');
		}
	}

	public function get_register()
	{
		$this->show_form('auth/register', '新規登録');
	}

	public function post_register()
	{
		$val = $this->validation();
		$val->field('password')->add_rule('min_length', 8);

		if ( ! $val->run())
		{
			return $this->show_form('auth/register', '新規登録', $val->error_message());
		}

		$email = $val->validated('email');
		$password = $val->validated('password');

		$user_id = null;

		if ( ! Model_User::exists($email))
		{
			// exists() のあとに同じメールアドレスの登録が割り込むと、create() も null を返す。
			// そのときも、下の「登録済み」と同じ扱いにする
			$user_id = Model_User::create($email, $password);
		}

		if ($user_id === null)
		{
			// 登録ボタンのダブルクリックでは、2回目のリクエストが1回目に作ったアカウントと重なる。
			// 2回目はログイン前のCookieで届くため、ログイン済みかでは見分けられない。
			// パスワードが合えば本人の再送信とみなしてログインさせ、「登録済み」のエラーを出さない
			$user = Model_User::authenticate($email, $password);

			if ($user === null)
			{
				return $this->show_form('auth/register', '新規登録', array('このメールアドレスはすでに登録されています'));
			}

			$user_id = $user['id'];
		}

		Guard::login($user_id);
		return $this->redirect_to('boards');
	}

	public function get_login()
	{
		$this->show_form('auth/login', 'ログイン');
	}

	public function post_login()
	{
		$val = $this->validation();
		$user = $val->run()
			? Model_User::authenticate($val->validated('email'), $val->validated('password'))
			: null;

		if ($user === null)
		{
			// 未登録かパスワード違いかを区別しない（登録済みのメールアドレスを知られないため）
			return $this->show_form('auth/login', 'ログイン', array('メールアドレスかパスワードが違います'));
		}

		Guard::login($user['id']);
		return $this->redirect_to('boards');
	}

	public function post_logout()
	{
		Guard::logout();
		return $this->redirect_to('auth/login');
	}

	/**
	 * @return Validation
	 */
	protected function validation()
	{
		$val = \Validation::forge();
		$val->add('email', 'メールアドレス')
			->add_rule('trim')
			->add_rule('required')
			->add_rule('valid_email')
			->add_rule('max_length', 255);
		// bcrypt は72バイトより後ろを無視するため、それより長いパスワードは受け付けない。
		// max_length は文字数で数え、全角1文字（3バイト）も1と数えるので使わない
		$val->add('password', 'パスワード')
			->add_rule('required')
			->add_rule(array('max_bytes' => function ($password) {
				return strlen($password) <= 72;
			}));

		$val->set_message('max_bytes', ':labelは半角72文字以内（全角は24文字以内）で入力してください');

		return $val;
	}

	/**
	 * エラー時にパスワードを入力欄に戻さないのは、パスワードを画面のHTMLに書き出さないため
	 *
	 * @param string $view
	 * @param string $title
	 * @param array  $errors
	 */
	protected function show_form($view, $title, array $errors = array())
	{
		$this->template->title = $title;
		$this->template->content = \View::forge($view, array(
			'email'  => (string) \Input::post('email', ''),
			'errors' => array_values($errors),
		));
	}
}
