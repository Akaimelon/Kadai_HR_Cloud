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
<body class="min-h-screen bg-gray-50 text-gray-900">
	<header class="border-b border-gray-200 bg-white">
		<div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
			<strong class="text-lg">採用カンバン</strong>
			<?php if (Guard::user_id() !== null): ?>
				<form method="post" action="<?php echo Uri::create('auth/logout'); ?>">
					<?php echo Form::csrf(); ?>
					<button type="submit" class="rounded px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100">ログアウト</button>
				</form>
			<?php endif; ?>
		</div>
	</header>
	<main class="mx-auto max-w-5xl px-4 py-8">
		<h1 class="mb-6 text-2xl font-bold"><?php echo $title; ?></h1>
		<?php echo $content; ?>
	</main>
</body>
</html>
