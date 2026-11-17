<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>403 | 採用カンバン</title>
	<?php echo Asset::css('app.css'); ?>
</head>
<body class="min-h-screen bg-canvas font-sans text-ink">
	<div class="flex min-h-screen flex-col">
		<?php // この画面は例外から直接作られ、ログイン中のメールアドレスを受け取れないので、ロゴだけを出す ?>
		<?php echo render('_header', array('user_email' => '')); ?>
		<main class="flex flex-1 items-center justify-center px-4 py-16">
			<div class="flex max-w-[460px] flex-col items-center gap-3.5 text-center">
				<p class="m-0 text-[64px] leading-none font-bold tracking-[0.04em] text-faint">403</p>
				<h1 class="m-0 text-[22px] font-bold">操作を受け付けられませんでした</h1>
				<p class="m-0 text-sm leading-[1.8] text-ink-soft">ページの有効期限が切れた可能性があります。前の画面を再読み込みしてから、もう一度操作してください。</p>
				<a href="<?php echo Uri::create('boards'); ?>"
					class="mt-2.5 inline-flex min-h-11 items-center rounded-lg bg-primary px-5 text-sm font-bold text-white no-underline hover:bg-primary-dark">ボード一覧へ戻る</a>
			</div>
		</main>
	</div>
</body>
</html>
