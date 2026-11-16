<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php // APIを呼ぶJSは、このトークンを X-CSRF-Token ヘッダに入れて送る ?>
	<meta name="csrf-token" content="<?php echo e(Security::fetch_token()); ?>">
	<title><?php echo $title; ?> | 採用カンバン</title>
</head>
<body>
	<header>
		<strong>採用カンバン</strong>
		<?php if (Guard::user_id() !== null): ?>
			<form method="post" action="<?php echo Uri::create('auth/logout'); ?>" style="display: inline;">
				<?php echo Form::csrf(); ?>
				<button type="submit">ログアウト</button>
			</form>
		<?php endif; ?>
	</header>
	<main>
		<h1><?php echo $title; ?></h1>
		<?php echo $content; ?>
	</main>
</body>
</html>
