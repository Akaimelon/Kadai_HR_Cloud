<?php

return array(
	// セッションの中身はサーバ側のファイルに保存し、Cookie にはセッションIDだけを入れる
	// 既定の Cookie ドライバはセッションの中身を Cookie に入れるので、Cookieを盗まれるとログアウト後も有効期限まで使用される
	// file ドライバならログアウト時にサーバ側のファイルを消すので、盗まれた Cookie も使えなくなる
	// サーバも一台なので、file ドライバで十分と判断
	'driver'           => 'file',

	// セッションは既定の null を false に変換するため、config.php の cookie.http_only は効かない
	'cookie_http_only' => true,
);
