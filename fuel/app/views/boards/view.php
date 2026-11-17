<p class="mb-4">
	<a href="<?php echo Uri::create('boards'); ?>" class="text-sm text-blue-600 hover:underline">← ボード一覧に戻る</a>
</p>

<?php // 列を狭くするとカードの文字が読めなくなるため、狭い画面では列を縮めずに横スクロールにする ?>
<div class="overflow-x-auto pb-2">
	<div class="grid min-w-3xl grid-cols-5 gap-3">
		<?php foreach ($columns as $column): ?>
			<section class="flex min-h-96 flex-col rounded-lg border border-gray-200 bg-gray-100">
				<h2 class="rounded-t-lg px-3 py-2 text-sm font-bold <?php echo $column['class']; ?>"><?php echo $column['title']; ?></h2>
				<div class="flex-1 p-2"></div>
			</section>
		<?php endforeach; ?>
	</div>
</div>
