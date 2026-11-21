# API の CSRF トークンは、Security::js_fetch_token() ではなく画面の meta で渡す

FuelPHP 1.8 には、JS で CSRF トークンを扱うための `Security::js_fetch_token()`（トークンを返す JS の関数を出力する）と `Security::js_set_token()`（フォームの隠し項目にトークンを入れる JS の関数を出力する）がある。どちらも、出力された関数が `document.cookie` から `fuel_csrf_token` の Cookie を読む。このアプリでは config.php の `cookie.http_only` で、CSRF の Cookie にも HttpOnly を付けている（ADR 0003）。HttpOnly の Cookie は JS から読めないので、これらの関数は空文字を返し、データを変える API（POST・PUT・DELETE）はすべて 403 になる。そこで、API のトークンは今までどおり `views/template.php` の `<meta name="csrf-token">` に `Security::fetch_token()` の値を埋め込み、ui.js の `callApi()` が読んで `X-CSRF-Token` ヘッダで送る。フォームは `Form::csrf()` でサーバーが隠し項目を埋め込むので、`js_set_token()` を使う場面もない。

## Consequences

- 使うには、CSRF の Cookie だけ HttpOnly を外す処理を classes/cookie.php に足す必要がある。トークンは meta からも JS で読めるので、外しても安全性はほとんど変わらないが、送るトークンの値もサーバーでの比べ方（ADR 0002）も今と同じで、得られるものがない
- 出力される JS は `var` を使い、宣言なしの変数でグローバル変数を作る（strict モードなら例外になる）。インラインの `<script>` なので、CSP の `script-src` を絞るときの妨げにもなる
- ui.js は meta がある画面（template.php を使う画面）でしか動かない。meta がないときは、callApi() が原因をコンソールに出して reject する
