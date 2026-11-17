<?php if ( ! empty($errors)): ?>
	<ul role="alert" class="mb-4 list-inside list-disc rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
		<?php foreach ($errors as $error): ?>
			<li><?php echo $error; ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
