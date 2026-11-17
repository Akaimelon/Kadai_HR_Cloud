<main id="boards" data-api-url="<?php echo Uri::create('api/boards'); ?>" data-board-url="<?php echo Uri::create('boards'); ?>"
	data-statuses="<?php echo $statuses_json; ?>"
	class="mx-auto flex w-full max-w-[1120px] flex-1 flex-col gap-6 px-6 pt-9 pb-14">
	<div class="flex flex-col gap-1.5">
		<h1 class="m-0 text-[26px] font-bold">ボード一覧</h1>
		<p class="m-0 text-sm text-muted">更新が新しい順に表示しています</p>
	</div>

	<form data-bind="submit: addBoard" class="flex flex-col gap-2 rounded-xl border border-line bg-white p-4">
		<div class="flex flex-wrap items-end gap-3">
			<div class="flex flex-[1_1_320px] flex-col gap-1.5">
				<label for="new-board-title" class="text-[13px] font-bold">新しいボード</label>
				<input type="text" id="new-board-title" maxlength="<?php echo Model_Board::TITLE_MAX_LENGTH; ?>" placeholder="ボード名（例：2027年度 夏インターン）"
					data-bind="textInput: newTitle"
					class="min-h-11 w-full rounded-lg border border-field px-3 py-2.5 text-sm text-ink focus:border-primary focus:outline-none">
			</div>
			<button type="submit" data-bind="disable: saving"
				class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-5 text-sm font-bold text-white hover:bg-primary-dark disabled:cursor-default disabled:opacity-50">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>作成
			</button>
		</div>
		<p role="alert" data-bind="visible: createError, text: createError" style="display: none;" class="m-0 text-[12.5px] text-danger"></p>
	</form>

	<?php // ボード名は利用者の入力なので、HTMLとして出さないよう text バインディングで表示する ?>
	<div data-bind="foreach: boards" class="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-4">
		<article class="flex flex-col gap-3.5 rounded-xl border border-line bg-white px-[18px] pt-[18px] pb-3.5 shadow-[0_1px_2px_rgba(16,24,40,0.05)]">
			<!-- ko ifnot: editing -->
				<div class="flex items-start justify-between gap-2">
					<div class="flex min-w-0 flex-col gap-1">
						<a data-bind="text: title, attr: { href: url }"
							class="text-[17px] leading-[1.4] font-bold break-all text-ink no-underline hover:text-primary"></a>
						<span class="text-[12.5px] text-muted"><span data-bind="text: cardCount"></span>名 ・ <span data-bind="text: updatedLabel"></span> 更新</span>
					</div>
					<div class="-mt-1.5 -mr-2 flex flex-none gap-0.5">
						<button type="button" data-bind="click: $parent.startEdit, disable: $parent.saving, attr: { 'aria-label': title() + ' の名前を変更' }"
							class="inline-flex size-9 cursor-pointer items-center justify-center rounded-lg text-muted hover:bg-canvas disabled:cursor-default disabled:opacity-50">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
						</button>
						<button type="button" data-bind="click: $parent.deleteBoard, disable: $parent.saving, attr: { 'aria-label': title() + ' を削除' }"
							class="inline-flex size-9 cursor-pointer items-center justify-center rounded-lg text-muted hover:bg-danger-tint hover:text-danger disabled:cursor-default disabled:opacity-50">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M6 6l1 14h10l1-14"></path></svg>
						</button>
					</div>
				</div>
			<!-- /ko -->
			<!-- ko if: editing -->
				<?php // submit バインディングはフォーム要素を渡すため、どのボードかを $data で渡す ?>
				<form data-bind="submit: function () { $parent.saveTitle($data); }" class="flex flex-col gap-2">
					<input type="text" maxlength="<?php echo Model_Board::TITLE_MAX_LENGTH; ?>" aria-label="ボード名"
						data-bind="textInput: draftTitle, hasFocus: true"
						class="min-h-10 w-full rounded-lg border border-field px-3 py-2 text-[15px] text-ink focus:border-primary focus:outline-none">
					<p role="alert" data-bind="visible: renameError, text: renameError" style="display: none;" class="m-0 text-[12.5px] text-danger"></p>
					<div class="flex justify-end gap-2">
						<button type="button" data-bind="click: $parent.cancelEdit, disable: $parent.saving"
							class="inline-flex min-h-9 cursor-pointer items-center rounded-lg border border-field bg-white px-3.5 text-[13px] text-ink hover:bg-canvas disabled:cursor-default disabled:opacity-50">キャンセル</button>
						<button type="submit" data-bind="disable: $parent.saving"
							class="inline-flex min-h-9 cursor-pointer items-center rounded-lg bg-primary px-4 text-[13px] font-bold text-white hover:bg-primary-dark disabled:cursor-default disabled:opacity-50">保存</button>
					</div>
				</form>
			<!-- /ko -->

			<div role="img" data-bind="attr: { 'aria-label': barLabel }, foreach: segments"
				class="flex h-2 gap-0.5 overflow-hidden rounded-full bg-column">
				<span data-bind="css: dot, style: { flexGrow: count }" class="basis-0"></span>
			</div>

			<?php // 「不採用・辞退」はラベルが2行になるので、数字を下に揃えて横の並びをずらさない ?>
			<div data-bind="foreach: counts" class="grid grid-cols-5 gap-1">
				<div class="flex flex-col justify-between gap-0.5">
					<span class="flex items-start gap-1 text-[11px] leading-tight text-muted">
						<span aria-hidden="true" data-bind="css: dot" class="mt-[3px] inline-block size-[7px] flex-none rounded-full"></span>
						<span data-bind="text: title"></span>
					</span>
					<span data-bind="text: count" class="pl-[11px] text-base font-bold"></span>
				</div>
			</div>
		</article>
	</div>

	<p data-bind="visible: loaded() && boards().length === 0" style="display: none;"
		class="m-0 rounded-xl border-[1.5px] border-dashed border-faint px-4 py-10 text-center text-sm text-muted">
		ボードがまだありません。上の入力欄から作成してください。
	</p>

	<?php echo render('_confirm_dialog'); ?>
	<?php echo render('_toast'); ?>
</main>

<?php echo Asset::js(array('knockout-3.5.1.js', 'ui.js', 'boards.js')); ?>
