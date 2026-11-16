<div id="boards" data-api-url="<?php echo Uri::create('api/boards'); ?>">
	<form data-bind="submit: addBoard">
		<label for="new-board-title">新しいボード</label>
		<input type="text" id="new-board-title" maxlength="100" placeholder="例：エンジニア採用 2027年度"
			data-bind="textInput: newTitle">
		<button type="submit" data-bind="disable: saving">作成</button>
	</form>

	<p role="alert" data-bind="visible: errorMessage, text: errorMessage" style="display: none;"></p>

	<?php // ボード名は利用者の入力なので、HTMLとして出さないよう text バインディングで表示する ?>
	<ul data-bind="foreach: boards">
		<li>
			<span data-bind="text: title"></span>
			（応募 <span data-bind="text: card_count"></span> 件）
		</li>
	</ul>

	<p data-bind="visible: loaded() && boards().length === 0" style="display: none;">
		ボードがまだありません。上の入力欄から作成してください。
	</p>
</div>

<?php echo Asset::js(array('knockout-3.5.1.js', 'boards.js')); ?>
