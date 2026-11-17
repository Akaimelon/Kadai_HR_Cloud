<div id="boards" data-api-url="<?php echo Uri::create('api/boards'); ?>">
	<form data-bind="submit: addBoard" class="mb-6 flex flex-wrap items-end gap-2">
		<div class="min-w-64 flex-1">
			<label for="new-board-title" class="mb-1 block text-sm font-medium">新しいボード</label>
			<input type="text" id="new-board-title" maxlength="100" placeholder="例：エンジニア採用 2027年度"
				data-bind="textInput: newTitle"
				class="w-full rounded border border-gray-300 bg-white px-3 py-2 focus:border-blue-500 focus:outline-none">
		</div>
		<button type="submit" data-bind="disable: saving"
			class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-50">作成</button>
	</form>

	<p role="alert" data-bind="visible: errorMessage, text: errorMessage" style="display: none;"
		class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></p>

	<?php // ボード名は利用者の入力なので、HTMLとして出さないよう text バインディングで表示する ?>
	<ul data-bind="foreach: boards" class="space-y-2">
		<li class="rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
			<!-- ko ifnot: editing -->
				<div class="flex flex-wrap items-center gap-2">
					<a data-bind="text: title, attr: { href: '<?php echo Uri::create('boards'); ?>/' + id }"
						class="font-medium break-all text-blue-700 hover:underline"></a>
					<span class="text-sm text-gray-500">（応募 <span data-bind="text: cardCount"></span> 件）</span>
					<span class="ml-auto flex gap-1">
						<button type="button" data-bind="click: $parent.startEdit, disable: $parent.saving"
							class="rounded px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-50">名前を変更</button>
						<button type="button" data-bind="click: $parent.deleteBoard, disable: $parent.saving"
							class="rounded px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50">削除</button>
					</span>
				</div>
			<!-- /ko -->
			<!-- ko if: editing -->
				<?php // submit バインディングはフォーム要素を渡すため、どのボードかを $data で渡す ?>
				<form data-bind="submit: function () { $parent.saveTitle($data); }" class="flex flex-wrap items-center gap-2">
					<input type="text" maxlength="100" aria-label="ボード名"
						data-bind="textInput: draftTitle, hasFocus: true"
						class="min-w-64 flex-1 rounded border border-gray-300 px-3 py-1.5 focus:border-blue-500 focus:outline-none">
					<button type="submit" data-bind="disable: $parent.saving"
						class="rounded bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">保存</button>
					<button type="button" data-bind="click: $parent.cancelEdit, disable: $parent.saving"
						class="rounded px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-50">キャンセル</button>
				</form>
			<!-- /ko -->
		</li>
	</ul>

	<p data-bind="visible: loaded() && boards().length === 0" style="display: none;"
		class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-gray-500">
		ボードがまだありません。上の入力欄から作成してください。
	</p>
</div>

<?php echo Asset::js(array('knockout-3.5.1.js', 'boards.js')); ?>
