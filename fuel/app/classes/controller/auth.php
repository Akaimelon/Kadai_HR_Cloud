<?php
/**
 * 登録・ログイン・ログアウト。
 *
 * 登録とログインはログインしていなくても開ける。POSTのCSRFトークンは
 * Controller_Base::before() が確認するので、ここでは確認しない。
 */
class Controller_Auth extends Controller_Base
{
	protected $public_actions = array('register', 'login');

	public function before()
	{
		parent::before();

		// ログイン済みで登録・ログイン画面を開いたら、ボード一覧へ
		if (in_array($this->request->action, $this->public_actions, true) and Guard::user_id() !== null)
		{
			\Response::redirect('boards');
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

		if (Model_User::exists($email))
		{
			return $this->show_form('auth/register', '新規登録', array('このメールアドレスはすでに登録されています'));
		}

		Guard::login(Model_User::create($email, $val->validated('password')));
		\Response::redirect('boards');
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
		\Response::redirect('boards');
	}

	public function post_logout()
	{
		Guard::logout();
		\Response::redirect('auth/login');
	}

	/**
	 * 登録とログインで共通の入力チェック
	 *
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
		// bcrypt は72バイトより後ろを無視するため、それより長いパスワードは受け付けない
		$val->add('password', 'パスワード')
			->add_rule('required')
			->add_rule('max_length', 72);

		$val->set_message('required', ':labelを入力してください');
		$val->set_message('valid_email', ':labelの形式が正しくありません');
		$val->set_message('min_length', ':labelは:param:1文字以上で入力してください');
		$val->set_message('max_length', ':labelは:param:1文字以内で入力してください');

		return $val;
	}

	/**
	 * 入力フォームを表示する。エラー時は入力したメールアドレスを残す（パスワードは残さない）
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
