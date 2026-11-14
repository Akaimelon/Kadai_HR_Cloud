# ログイン時はSession::rotate()ではなく、セッションを破棄して作り直す

セッション固定化を防ぐため、ログイン成功時は `Session::destroy()` → `Session::create()` → `Session::set('user_id', …)` の順でセッションを作り直す。FuelPHP 1.8 の `Session::rotate()` はセッションIDを変えるが、fileドライバでは古いIDのファイルに「新しいIDへの転送先」を残す（同時に来たAJAXでセッションが切れないようにするため）。そのため、ログイン前に攻撃者が仕込んだセッションIDでも、ログイン後のセッションに入れてしまう（2026-11-15にcurlで確認）。

## Consequences

- 設計書0章 No.4、6章のシーケンス図、7章のセキュリティ表の「Session::rotate()」は、この方法に読み替える
- FuelPHPがログイン後に一定時間ごとに行う自動のrotate（`rotation_time`）はそのまま使う。ログイン後のIDは攻撃者に知られていないため問題にならない
