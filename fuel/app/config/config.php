<?php
/**
 * FuelPHP の既定値（fuel/core/config/config.php）から変える設定だけを書く。
 * 既定値のままの項目は書かない。変えた設定が既定値のコメントに埋もれないようにするため。
 */
return array(

	// Validation などのメッセージを lang/ja から読むため。ja にないファイルは en を使う
	'language' => 'ja',

	'security' => array(
		/**
		 * CSRFトークンを24時間使い続ける
		 * 0（既定）だとリクエストのたびにトークンが作り直され、
		 * 複数タブで開いたときや、画面表示のあとにAPIを呼んだときに古いトークンが弾かれるため
		 */
		'csrf_expiration' => 86400,

		// URL の文字列を htmlentities にかけてから使う。URL に入れられたタグが、そのまま HTML として出ないようにするため
		'uri_filter' => array('htmlentities'),

		// View に渡した値を自動でエスケープする（設計書7章「XSS」）。書かないと FuelPHP が起動時に例外を投げる
		'output_filter' => array('Security::htmlentities'),

		// 自動エスケープで、View の中に入れ子にした View などを文字列に変えずにそのまま渡すため
		'whitelisted_classes' => array(
			'Fuel\\Core\\Presenter',
			'Fuel\\Core\\Response',
			'Fuel\\Core\\View',
			'Fuel\\Core\\ViewModel',
			'Closure',
		),
	),

	/**
	 * Cookieの既定の属性
	 *
	 * http_only : JavaScriptからCookieを読めなくする。セッションCookieには効かないため、
	 *             config/session.php の cookie_http_only でも指定している
	 * samesite  : 別サイトからのPOSTにCookieを付けない（CSRFトークンに重ねる対策）
	 *             FuelPHP 1.8 の Cookie クラスにはないため、classes/cookie.php で追加している
	 */
	'cookie' => array(
		'secure'    => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
		'http_only' => true,
		'samesite'  => 'Lax',
	),
);
