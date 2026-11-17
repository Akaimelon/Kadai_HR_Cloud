<?php
/**
 * 本番環境のDB接続設定。fuel/app/config/db.php の設定（文字コード）に上書きで合わさる。
 *
 * 接続情報はリポジトリに書かず、サーバーの環境変数から読む。
 * パスワードをコミットすると、リポジトリを見られる人全員に知られてしまうため（設計書7章「情報漏えい」）。
 */
return array(
	'default' => array(
		'type'        => 'mysqli',
		'connection'  => array(
			'hostname'   => getenv('DB_HOST'),
			'database'   => getenv('DB_NAME'),
			'username'   => getenv('DB_USER'),
			'password'   => getenv('DB_PASSWORD'),
			'persistent' => false,
		),
		'identifier'   => '`',
		'table_prefix' => '',
		// 本番でSQLを溜め込んで画面に出さないため
		'profiling'    => false,
	),
);
