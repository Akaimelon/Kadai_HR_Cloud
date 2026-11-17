<div id="board" data-api-url="<?php echo Uri::create('api/boards/'.$board_id.'/cards'); ?>"
	data-card-api-url="<?php echo Uri::create('api/cards'); ?>">
	<p class="mb-4">
		<a href="<?php echo Uri::create('boards'); ?>" class="text-sm text-blue-600 hover:underline">← ボード一覧に戻る</a>
	</p>

	<p role="alert" data-bind="visible: errorMessage, text: errorMessage" style="display: none;"
		class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></p>

	<?php // 列を狭くするとカードの文字が読めなくなるため、狭い画面では列を縮めずに横スクロールにする ?>
	<div class="overflow-x-auto pb-2">
		<div class="grid min-w-3xl grid-cols-5 gap-3">
			<?php foreach ($columns as $column): ?>
				<section data-status-id="<?php echo $column['id']; ?>" data-bind="with: columns[<?php echo $column['id']; ?>]"
					class="flex min-h-96 flex-col rounded-lg border border-gray-200 bg-gray-100">
					<h2 class="rounded-t-lg px-3 py-2 text-sm font-bold <?php echo $column['class']; ?>"><?php echo $column['title']; ?></h2>
					<?php // カードの上だけでなく列の空いているところにも落とせるよう、列の本体でドロップを受ける ?>
					<div data-bind="event: { dragover: $root.dragOver, dragleave: $root.dragLeave, drop: $root.drop },
							css: { 'bg-blue-50': $root.isDropColumn($data) }"
						class="flex-1 space-y-2 rounded-b-lg p-2">
						<?php // 応募者名とメモは利用者の入力なので、HTMLとして出さないよう text バインディングで表示する ?>
						<ul data-bind="foreach: cards" class="space-y-2">
							<?php // 編集中にドラッグできると、入力欄の文字を選べなくなるため止める ?>
							<li data-bind="attr: { draggable: editing() || $root.saving() ? 'false' : 'true' },
									event: { dragstart: $root.dragStart, dragend: $root.dragEnd },
									css: {
										'opacity-50': $root.draggingCard() === $data,
										'cursor-grab': !editing(),
										'before:absolute before:inset-x-0 before:-top-1.5 before:h-0.5 before:rounded before:bg-blue-500': $root.isDropAt($parent, $index()),
										'after:absolute after:inset-x-0 after:-bottom-1.5 after:h-0.5 after:rounded after:bg-blue-500': $index() === $parent.cards().length - 1 && $root.isDropAt($parent, $index() + 1)
									}"
								class="relative rounded border border-gray-200 bg-white px-3 py-2 shadow-sm">
								<!-- ko ifnot: editing -->
									<p data-bind="text: title" class="text-sm font-medium break-all"></p>
									<p data-bind="visible: memo, text: memo" class="mt-1 text-xs whitespace-pre-wrap break-all text-gray-600"></p>
									<p class="mt-1 text-xs text-gray-400">登録日 <span data-bind="text: createdOn"></span></p>
									<?php // 列の幅が狭く、登録日と同じ行に置くとボタンの文字が折り返すため、行を分ける ?>
									<div class="mt-1 flex justify-end gap-1">
										<button type="button" data-bind="click: $root.startEdit, disable: $root.saving"
											class="rounded px-1.5 py-0.5 text-xs text-gray-600 hover:bg-gray-100 disabled:opacity-50">編集</button>
										<button type="button" data-bind="click: $root.deleteCard, disable: $root.saving"
											class="rounded px-1.5 py-0.5 text-xs text-red-600 hover:bg-red-50 disabled:opacity-50">削除</button>
									</div>
								<!-- /ko -->
								<!-- ko if: editing -->
									<form data-bind="submit: function () { $root.saveCard($data); }" class="space-y-2">
										<input type="text" maxlength="100" aria-label="応募者名"
											data-bind="textInput: draftTitle, hasFocus: true"
											class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none">
										<textarea rows="3" maxlength="1000" aria-label="メモ" placeholder="メモ（任意）"
											data-bind="textInput: draftMemo"
											class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none"></textarea>
										<div class="flex gap-1">
											<button type="submit" data-bind="disable: $root.saving"
												class="rounded bg-blue-600 px-2 py-1 text-sm font-medium whitespace-nowrap text-white hover:bg-blue-700 disabled:opacity-50">保存</button>
											<button type="button" data-bind="click: $root.cancelEdit, disable: $root.saving"
												class="rounded px-2 py-1 text-sm whitespace-nowrap text-gray-600 hover:bg-gray-100 disabled:opacity-50">キャンセル</button>
										</div>
									</form>
								<!-- /ko -->
							</li>
						</ul>

						<!-- ko ifnot: adding -->
							<button type="button" data-bind="click: $root.startAdd, disable: $root.saving"
								class="w-full rounded px-2 py-1.5 text-left text-sm text-gray-600 hover:bg-gray-200 disabled:opacity-50">＋追加</button>
						<!-- /ko -->
						<!-- ko if: adding -->
							<?php // submit バインディングはフォーム要素を渡すため、どの列かを $data で渡す ?>
							<form data-bind="submit: function () { $root.addCard($data); }" class="space-y-2 rounded border border-gray-200 bg-white p-2">
								<input type="text" maxlength="100" aria-label="応募者名" placeholder="応募者名"
									data-bind="textInput: newTitle, hasFocus: true"
									class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none">
								<textarea rows="3" maxlength="1000" aria-label="メモ" placeholder="メモ（任意）"
									data-bind="textInput: newMemo"
									class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none"></textarea>
								<div class="flex gap-1">
									<button type="submit" data-bind="disable: $root.saving"
										class="rounded bg-blue-600 px-3 py-1 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">追加</button>
									<button type="button" data-bind="click: $root.cancelAdd, disable: $root.saving"
										class="rounded px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-50">キャンセル</button>
								</div>
							</form>
						<!-- /ko -->
					</div>
				</section>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<?php echo Asset::js(array('knockout-3.5.1.js', 'board.js')); ?>
