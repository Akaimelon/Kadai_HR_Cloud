<?php
return array(
	// トップページはボード一覧。未ログインならログイン画面にリダイレクトされる
	'_root_'  => 'boards/index',
	'_404_'   => 'welcome/404',    // The main 404 route

	'hello(/:name)?' => array('welcome/hello', 'name' => 'hello'),

	// 登録・ログイン・ログアウト（GETとPOSTで Controller_Auth の get_〜 / post_〜 に分かれる）
	'auth/register' => 'auth/register',
	'auth/login'    => 'auth/login',
	'auth/logout'   => 'auth/logout',

	// ボード一覧
	'boards' => 'boards/index',
);
