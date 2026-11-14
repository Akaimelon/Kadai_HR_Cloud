# CSRFトークンはcheck_token()ではなく、fetch_token()とhash_equals()で比べる

FuelPHP 1.8 の `Security::check_token()` は、確認するたびにトークンを作り直す。画面の `<meta name="csrf-token">` に埋め込んだトークンでAPIを何度も呼ぶ作りだと、2回目以降が403になる。そこで `config.php` の `security.csrf_expiration` を0より大きく（1日）して、トークンを作り直さないようにする。そのうえで、送られてきた値を `hash_equals(\Security::fetch_token(), $token)` で比べる。`hash_equals()` を使うのは、比較にかかる時間から値を推測されないようにするため。

## Consequences

- トークンは1日同じものを使う。1日以上開いたままの画面から操作すると403になるため、画面の再読み込みを促すメッセージを返す
- フォームの隠し項目は、これまでどおり `Form::csrf()` で埋め込める
