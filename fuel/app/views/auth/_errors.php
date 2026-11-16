<?php if ( ! empty($errors)): ?>
	<ul role="alert">
		<?php foreach ($errors as $error): ?>
			<li><?php echo $error; ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
