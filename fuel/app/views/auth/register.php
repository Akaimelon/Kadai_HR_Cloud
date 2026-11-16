<?php echo render('auth/_errors', array('errors' => $errors)); ?>
<form method="post" action="<?php echo Uri::create('auth/register'); ?>">
	<?php echo Form::csrf(); ?>
	<p>
		<label for="email">メールアドレス</label><br>
		<input type="email" id="email" name="email" value="<?php echo $email; ?>" maxlength="255" required autofocus>
	</p>
	<p>
		<label for="password">パスワード（8文字以上、半角72文字以内）</label><br>
		<input type="password" id="password" name="password" minlength="8" maxlength="72" required>
	</p>
	<p><button type="submit">登録する</button></p>
</form>
<p><a href="<?php echo Uri::create('auth/login'); ?>">登録済みの方はログイン</a></p>
