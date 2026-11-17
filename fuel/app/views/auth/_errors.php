<?php if ( ! empty($errors)): ?>
	<div role="alert" class="flex gap-2 rounded-lg border border-danger-line bg-danger-tint px-3 py-2.5 text-[13.5px] text-danger">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" class="mt-0.5 flex-none"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16h.01"></path></svg>
		<ul class="m-0 list-none space-y-1 p-0">
			<?php foreach ($errors as $error): ?>
				<li><?php echo $error; ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
