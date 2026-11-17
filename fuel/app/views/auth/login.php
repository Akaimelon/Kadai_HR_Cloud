<form method="post" action="<?php echo Uri::create('auth/login'); ?>"
	class="flex flex-col gap-5 rounded-[14px] border border-line bg-white p-8 shadow-[0_4px_16px_rgba(16,24,40,0.06)]">
	<?php echo Form::csrf(); ?>
	<h1 class="m-0 text-[22px] font-bold">ログイン</h1>
	<?php echo render('auth/_errors', array('errors' => $errors)); ?>
	<div class="flex flex-col gap-1.5">
		<label for="email" class="text-[13px] font-bold">メールアドレス</label>
		<input type="email" id="email" name="email" value="<?php echo $email; ?>" maxlength="255" required autofocus
			autocomplete="email" placeholder="you@example.com"
			class="min-h-[46px] w-full rounded-lg border border-field px-3 py-2.5 text-[15px] text-ink focus:border-primary focus:outline-none">
	</div>
	<div class="flex flex-col gap-1.5">
		<label for="password" class="text-[13px] font-bold">パスワード</label>
		<input type="password" id="password" name="password" maxlength="72" required autocomplete="current-password"
			class="min-h-[46px] w-full rounded-lg border border-field px-3 py-2.5 text-[15px] text-ink focus:border-primary focus:outline-none">
	</div>
	<button type="submit" class="flex min-h-12 cursor-pointer items-center justify-center rounded-lg bg-primary px-4.5 py-3 text-[15px] font-bold text-white hover:bg-primary-dark">ログイン</button>
</form>
<p class="m-0 text-center text-sm text-ink-soft">アカウントをお持ちでない方は <a href="<?php echo Uri::create('auth/register'); ?>" class="font-bold text-primary hover:text-primary-dark">新規登録</a></p>
