# SameSite属性を付けるため、FuelPHPのCookieクラスを上書きする

FuelPHP 1.8 の `Cookie::set()` は、`setcookie()` にSameSiteを渡さない。PHP 7.3 では、pathに `; SameSite=Lax` を混ぜる古い回避策も警告になって失敗する。そこで `fuel/app/classes/cookie.php` で `Cookie::set()` だけを上書きし、PHP 7.3 から使える配列オプションで `samesite` を渡す。そのクラスを `bootstrap.php` の `Autoloader::add_classes()` でコアの Cookie と差し替える。HttpOnly・SameSite・Secure は `config.php` の `cookie` にまとめ、セッションCookieとCSRFトークンのCookieの両方に効かせる。

## Considered Options

- Apacheの `Header edit Set-Cookie` で付ける：mod_headers は有効だが、アプリの外（Docker設定）に隠れてしまい、設計書の「configファイルで設定する」と合わない
- FuelPHPを新しい版に上げる：課題の想定環境（1.8系）から外れる
