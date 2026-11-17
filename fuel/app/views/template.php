<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php // APIを呼ぶJSは、このトークンを X-CSRF-Token ヘッダに入れて送る ?>
	<meta name="csrf-token" content="<?php echo e(Security::fetch_token()); ?>">
	<title><?php echo $title; ?> | 採用カンバン</title>
	<?php echo Asset::css('app.css'); ?>
</head>
<body class="min-h-screen bg-canvas font-sans text-ink">
	<?php if ($user_email !== ''): ?>
		<div class="flex min-h-screen flex-col">
			<?php echo render('_header', array('user_email' => $user_email)); ?>
			<?php echo $content; ?>
		</div>
	<?php else: ?>
		<?php // ログイン前の画面（ログイン・新規登録）は、ヘッダーを出さずにフォームを中央に置く ?>
		<div class="flex min-h-screen items-center justify-center px-4 py-12">
			<main class="flex w-full max-w-[420px] flex-col gap-6">
				<div class="flex items-center justify-center gap-2.5 text-lg font-bold">
					<?php echo render('_logo', array('size' => 30)); ?>
					<span>採用カンバン</span>
				</div>
				<?php echo $content; ?>
			</main>
		</div>
	<?php endif; ?>
</body>
</html>
