<?php
return array(
	// 未ログインなら Controller_Base がログイン画面にリダイレクトする
	'_root_'  => 'boards/index',
	'_404_'   => 'welcome/404',    // The main 404 route

	'hello(/:name)?' => array('welcome/hello', 'name' => 'hello'),

	// 以下は書かなくても URL からコントローラに振り分けられるが、どの URL があるかを一覧するために書く
	'auth/register' => 'auth/register',
	'auth/login'    => 'auth/login',
	'auth/logout'   => 'auth/logout',
	'boards' => 'boards/index',
	'boards/(:num)' => 'boards/view/$1',

	'api/boards' => 'api/boards/index',
	'api/boards/(:num)' => 'api/boards/item/$1',
	'api/boards/(:num)/cards' => 'api/cards/index/$1',
	'api/cards/(:num)' => 'api/cards/item/$1',
	'api/cards/(:num)/move' => 'api/cards/move/$1',
);
