# インターン課題環境構築手順

## Dockerの基本知識
Dockerの基本的な概念については、以下のリンクを参考にしてください：
- [Docker入門（1）](https://qiita.com/Sicut_study/items/4f301d000ecee98e78c9)
- [Docker入門（2）](https://qiita.com/takusan64/items/4d622ce1858c426719c7)

## セットアップ手順

手順3からは `docker` ディレクトリで実行します。Git Bash では、`-w` を使うコマンドの先頭に `MSYS_NO_PATHCONV=1 ` を付けます（`-w` のパスが Windows のパスに書き換えられてしまうため）。

1. **リポジトリをクローン**
   ```bash
   git clone <リポジトリURL>
   ```

2. **dockerディレクトリに移動**
   ```bash
   cd <クローンしたディレクトリ>/docker
   ```

3. **Dockerイメージのビルド**
   ```bash
   docker compose build
   ```

4. **コンテナの起動**
   ```bash
   docker compose up -d
   ```
   PCの3306番をほかの MySQL（Windows に入れた MySQL など）が使っていると、`ports are not available: exposing port TCP 0.0.0.0:3306` のエラーになり、コンテナが起動しません。そのときは、dbのポートをPCに公開しない上書きファイル `compose.noport.yml` を足して起動します。
   ```bash
   docker compose -f docker-compose.yml -f compose.noport.yml up -d
   ```
   - アプリは Docker のネットワークの中で `db` という名前でDBに接続するので、公開を外しても動きます。PCから MySQL Workbench などで直接つなぐことはできなくなります
   - `!reset` を使うので、Docker Compose 2.24 以上が必要です（`docker compose version` で確認できます）
   - 以降の `docker compose exec` は、どちらで起動した場合も `-f` なしで実行できます

5. **テーブルを作る**（マイグレーション。初回と、マイグレーションを追加したとき）
   開発用DB `fuelphp` に、users・boards・statuses・cards のテーブルを作ります。DB名は `docker-compose.yml` の `MYSQL_DATABASE` と `fuel/app/config/development/db.php` で揃えてあるので、変えずに使います。
   ```bash
   docker compose exec -w /var/www/html/my_fuel_project app php oil refine migrate
   ```
   - Dockerfile に WORKDIR がないため、`-w` でプロジェクトのフォルダを指定します
   - dbコンテナは起動してから数十秒は接続を受け付けません。`Connection refused` のエラーになったら、少し待ってからやり直してください

6. **ブラウザで http://localhost にアクセス**
   ログイン画面が表示されます。「新規登録」からアカウントを作ると、ボード一覧を使えます。

## 開発環境の準備

### Tailwind CSS のビルド
画面のスタイルは Tailwind CSS で書きます。ビルド結果の `public/assets/css/app.css` はコミット済みなので、画面を表示するだけならこの手順は不要です。views や js のクラス名を変えたときは、ビルドし直して `app.css` も一緒にコミットしてください（理由は `docs/adr/0004-tailwind-standalone-cli-in-container.md`）。

1. **CLIをダウンロード**（初回だけ。リポジトリ直下で実行）
   Node.js が要らないスタンドアロンCLIの、Linux用の実行ファイル（約100MB）を `tailwind/tailwindcss` に置きます。appコンテナの中で動かすので、自分のPCのOSに関係なくLinux用を使います。`.gitignore` で除外しているのでコミットされません。
   ```bash
   curl -fsSL -o tailwind/tailwindcss https://github.com/tailwindlabs/tailwindcss/releases/download/v4.3.3/tailwindcss-linux-x64
   ```

2. **appコンテナの中でビルド**（`docker` ディレクトリで実行。コンテナは起動しておく）
   ```bash
   docker compose exec -w /var/www/html/my_fuel_project app sh -c "chmod +x tailwind/tailwindcss && ./tailwind/tailwindcss -i tailwind/input.css -o public/assets/css/app.css --minify"
   ```
   - Dockerfile に WORKDIR がないため、`-w` でプロジェクトのフォルダを指定します
   - Git Bash では `-w` のパスが書き換えられてしまうので、先頭に `MSYS_NO_PATHCONV=1 ` を付けます

クラス名を探す場所は `tailwind/input.css` の `@source` で指定しています（`fuel/app/views`、`public/assets/js`（`bootstrap*.js` を除く）、`fuel/app/config/kanban.php`）。

### テスト（PHPUnit）
テストは `php oil test` で実行します。PHPUnit 5.7 は appコンテナのイメージに入っています（`docker/Dockerfile`）。FuelPHP 1.8 の `TestCase` が `PHPUnit_Framework_TestCase` を継承しているため、それがある5系を使います。テストは `fuel/app/tests/` に置き、`fuel/app/phpunit.xml` の設定でアプリのテストだけを実行します。

以下のコマンドは `docker` ディレクトリで実行します。Git Bash では先頭に `MSYS_NO_PATHCONV=1 ` を付けます。

1. **appコンテナを作り直す**（PHPUnit が入る前にコンテナを作った人だけ。`docker compose exec app phpunit --version` で確認できます）
   ```bash
   docker compose build app
   docker compose up -d app
   ```

2. **テスト用DBを作る**（初回だけ）
   テストは `FUEL_ENV=test` で動き、`fuel/app/config/test/db.php` のテスト用DB `fuelphp_test` に接続します。開発用DBのデータを消さないよう、別のDBにしています。
   ```bash
   docker compose exec db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS fuelphp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   ```

3. **テスト用DBにテーブルを作る**（初回と、マイグレーションを追加したとき）
   ```bash
   docker compose exec -e FUEL_ENV=test -w /var/www/html/my_fuel_project app php oil refine migrate
   ```

4. **テストを実行する**
   ```bash
   docker compose exec -w /var/www/html/my_fuel_project app php oil test
   ```
   最後に `OK (... tests, ... assertions)` と出れば成功です。失敗したテストがあると、終了コードが1になります。

## PHP周りのバージョン
- **PHP**: 7.3
- **FuelPHP**: 1.8

## ログについて
- **アクセスログ**: Dockerのコンテナのログ
- **FuelPHPのエラーログ**: /var/www/html/my_fuel_project/fuel/app/logs/
  - 年月日ごとにログが管理されている
  - tail -f {見たいログファイル}でログを出力

## MySQLコンテナ設定
このプロジェクトには、MySQLを使用するDBコンテナが含まれています。設定は以下の通りです。

- **MySQLバージョン**: 8.0
- **ポート**: `3306`
- **環境変数**:
  - `MYSQL_ROOT_PASSWORD`: root
  - `MYSQL_DATABASE`: fuelphp

### アクセス情報
- **ホスト**: `localhost`
- **ポート**: `3306`（`compose.noport.yml` を足して起動した場合は、PCからはつなげません）
- **ユーザー名**: `root`
- **パスワード**: `root`
- **データベース名**: `fuelphp`（テスト用は `fuelphp_test`）
