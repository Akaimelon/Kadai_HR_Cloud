# Tailwind CSS はスタンドアロンCLIをappコンテナの中で動かし、ビルド結果をコミットする

スタイリングは Tailwind CSS を使う。knockout の `css` バインディングでクラスを付け外しするだけで見た目を切り替えられ、Node.jsなしでビルドでき、使ったクラスだけを出力するのでCSSが小さく済むため（設計書2章）。ビルドには、Node.js が要らないスタンドアロンCLI（実行ファイル1つ）の v4.3.3 を使う。どのPCでも同じになるよう、Linux用の実行ファイルを appコンテナの中で動かす。実行ファイルは各自がダウンロードして `.gitignore` で除外し、ビルド結果の `public/assets/css/app.css` はコミットする。こうすれば、CLIがなくても画面を表示できる。

## Considered Options

- 自前のCSSを1ファイルで書く：ビルドが要らず一番単純だが、設計書2章の理由（cssバインディングでの切り替え、出力の小ささ）を優先した
- 同梱の Bootstrap 3：古く、カンバン部分は結局自前のCSSが要る
- 各自のPCでCLIを動かす：OSごとに実行ファイルが違い、手順が分かれる
- ビルド用のシェルスクリプトを置く：`core.autocrlf=true` の環境では改行がCRLFになり、コンテナで動かない。そのため、READMEにコマンドを書く

## Consequences

- クラス名を変えたPRでは、ビルドし直した `app.css` も一緒にコミットする
- `tailwind/input.css` は `source(none)` で自動探索を止め、`@source` で views・js（`bootstrap*.js` は除く）・`config/kanban.php` だけを探索する
- `docker compose exec` には `-w /var/www/html/my_fuel_project` が要る（Dockerfile に WORKDIR がないため）
