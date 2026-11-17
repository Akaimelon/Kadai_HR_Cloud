# 認証はAuthパッケージを使わず、SessionとDBクラスで自前実装する

FuelPHP 1.8 の Auth パッケージ（SimpleAuth）を比べたうえで、使わないことにした。使うのはログイン・ログアウト・登録だけで、このプロジェクトのセキュリティ要件を満たすには、SimpleAuth の中心の処理を上書きするか、前後に処理を足す必要がある。その分で「便利さ」がほぼなくなり、パッケージの癖を理解する手間だけが残る。自前実装なら `Guard` と `Model_User` の約180行（コメント込み）で済み、課題条件 No.4（sessionやcookieを使う）も、`Session::set('user_id')` を自分で書いていることで説明できる。

## Considered Options

- **SimpleAuth をそのまま使う**：ログイン時のセッション固定化を防げない。`Auth::login()` は中で `Session::rotate()` を呼ぶ（`fuel/packages/auth/classes/auth/login/simpleauth.php:164`）ため、ADR 0001 と同じ問題が起きる。2026-11-17 に curl で、ログイン前に仕込んだセッションIDのままログイン後のセッションに入れることを確かめた
- **SimpleAuth を上書きして使う**：要件は満たせるが、次の手当てが要る
  - `Auth::login()` の前に `Session::destroy()` → `Session::create()` を呼ぶ。2026-11-17 に curl で、こうすると仕込んだIDでは入れなくなることを確かめた
  - パスワードの保存を `password_hash` にするため、`hash_password()` と `validate_user()` を上書きする。SimpleAuth は全ユーザー共通のソルトの PBKDF2-SHA256（`driver.php:176`）で、入力をハッシュにして `WHERE password = ?` で照合する作りなので、`password_verify()` を使うには照合の処理ごと書き直すことになる
  - users テーブルに、使わない `username`・`group`・`profile_fields`・`last_login`・`login_hash` を足し、`created_at` を int にする
  - 秘密の値（`auth.salt`、`simpleauth.login_hash_salt`）をコミットしない仕組みを用意する
- **OrmAuth**：グループ・ロール・権限のテーブルが増える。権限の管理は要件にない

SimpleAuth の利点（同時ログインの制限、remember me、グループ・ロールの権限、パスワードの再発行）は、どれも課題の要件にない。
