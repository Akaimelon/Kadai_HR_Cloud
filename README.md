# インターン課題環境構築手順

## Dockerの基本知識
Dockerの基本的な概念については、以下のリンクを参考にしてください：
- [Docker入門（1）](https://qiita.com/Sicut_study/items/4f301d000ecee98e78c9)
- [Docker入門（2）](https://qiita.com/takusan64/items/4d622ce1858c426719c7)

## セットアップ手順

1. **リポジトリをクローン**
   ```bash
   git clone <リポジトリURL>
   ```

2. **dockerディレクトリに移動**
   ```bash
   cd docker
   ```

3. **データベース名の設定**
   `docker-compose.yml` 内の `db` サービスにある `MYSQL_DATABASE` の値を、各自任意のデータベース名に設定してください。
   
   例:
   ```yaml
   environment:
     MYSQL_ROOT_PASSWORD: root
     MYSQL_DATABASE: <your_database_name>  # 任意のデータベース名を指定
   ```

4. **Dockerイメージのビルド**
   ```bash
   docker-compose build
   ```

5. **コンテナの起動**
   ```bash
   docker-compose up -d
   ```
6. **ブラウザからlocalhostにアクセス**

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

## PHP周りのバージョン
- **PHP**: 7.3
- **FuelPHP**: 1.8

## ログについて
- **アクセスログ**: Dockerのコンテナのログ
- **FuelPHPのエラーログ**: /var/www/html/intern_kadai/fuel/app/logs/
  - 年月日ごとにログが管理されている
  - tail -f {見たいログファイル}でログを出力

## MySQLコンテナ設定
このプロジェクトには、MySQLを使用するDBコンテナが含まれています。設定は以下の通りです。

- **MySQLバージョン**: 8.0
- **ポート**: `3306`
- **環境変数**:
  - `MYSQL_ROOT_PASSWORD`: root
  - `MYSQL_DATABASE`: 各自設定したデータベース名

### アクセス情報
- **ホスト**: `localhost`
- **ポート**: `3306`
- **ユーザー名**: `root`
- **パスワード**: `root`
- **データベース名**: 各自設定した名前
