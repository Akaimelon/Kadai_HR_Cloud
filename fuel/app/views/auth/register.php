<div class="max-w-sm rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
	<?php echo render('auth/_errors', array('errors' => $errors)); ?>
	<form method="post" action="<?php echo Uri::create('auth/register'); ?>" class="space-y-4">
		<?php echo Form::csrf(); ?>
		<div>
			<label for="email" class="mb-1 block text-sm font-medium">メールアドレス</label>
			<input type="email" id="email" name="email" value="<?php echo $email; ?>" maxlength="255" required autofocus
				class="w-full rounded border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
		</div>
		<div>
			<label for="password" class="mb-1 block text-sm font-medium">パスワード（8文字以上、半角72文字以内）</label>
			<input type="password" id="password" name="password" minlength="8" maxlength="72" required
				class="w-full rounded border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
		</div>
		<button type="submit" class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">登録する</button>
	</form>
	<p class="mt-4 text-sm"><a href="<?php echo Uri::create('auth/login'); ?>" class="text-blue-600 hover:underline">登録済みの方はログイン</a></p>
</div>
