<header class="flex min-h-[60px] flex-wrap items-center justify-between gap-4 border-b border-line bg-white px-6 py-2.5">
	<a href="<?php echo Uri::create('boards'); ?>" class="flex items-center gap-2.5 text-base font-bold text-ink no-underline">
		<?php echo render('_logo', array('size' => 26)); ?>
		<span>採用カンバン</span>
	</a>
	<?php if ($user_email !== ''): ?>
		<div class="flex items-center gap-4">
			<span class="text-[13px] text-muted"><?php echo $user_email; ?></span>
			<form method="post" action="<?php echo Uri::create('auth/logout'); ?>">
				<?php echo Form::csrf(); ?>
				<button type="submit" class="inline-flex min-h-9 cursor-pointer items-center rounded-lg border border-field bg-white px-3.5 text-[13px] text-ink hover:bg-canvas">ログアウト</button>
			</form>
		</div>
	<?php endif; ?>
</header>
