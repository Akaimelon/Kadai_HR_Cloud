<?php // ボード名・応募者名・メモは利用者の入力なので、HTMLとして出さないよう text バインディングで表示する ?>
<main id="board"
	data-api-url="<?php echo Uri::create('api/boards/'.$board['id'].'/cards'); ?>"
	data-card-api-url="<?php echo Uri::create('api/cards'); ?>"
	data-board-api-url="<?php echo Uri::create('api/boards/'.$board['id']); ?>"
	data-boards-url="<?php echo Uri::create('boards'); ?>"
	data-title="<?php echo $board['title']; ?>"
	data-updated-at="<?php echo $board['updated_at']; ?>"
	data-statuses="<?php echo $statuses_json; ?>"
	data-card-title-max="<?php echo Model_Card::TITLE_MAX_LENGTH; ?>"
	class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-[18px] px-6 pt-5 pb-12">
	<nav aria-label="パンくずリスト" class="flex min-w-0 items-center gap-2 text-[13px] text-muted">
		<a href="<?php echo Uri::create('boards'); ?>" class="flex-none text-primary no-underline hover:text-primary-dark">ボード一覧</a>
		<span aria-hidden="true">/</span>
		<span data-bind="text: title" class="truncate"></span>
	</nav>

	<div class="flex flex-wrap items-end justify-between gap-4">
		<div class="flex min-w-0 flex-col gap-1.5">
			<!-- ko ifnot: renaming -->
				<div class="flex items-center gap-1.5">
					<h1 data-bind="text: title" class="m-0 text-[26px] font-bold tracking-[0.01em] break-all"></h1>
					<button type="button" aria-label="ボード名を変更" data-bind="click: startRename, disable: saving"
						class="inline-flex size-9 flex-none cursor-pointer items-center justify-center rounded-lg text-muted hover:bg-white disabled:cursor-default disabled:opacity-50">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
					</button>
				</div>
			<!-- /ko -->
			<!-- ko if: renaming -->
				<form data-bind="submit: saveTitle" class="flex w-[min(640px,100%)] flex-col gap-1.5">
					<div class="flex items-center gap-2">
						<input type="text" maxlength="<?php echo Model_Board::TITLE_MAX_LENGTH; ?>" aria-label="ボード名"
							data-bind="textInput: draftTitle, hasFocus: true"
							class="min-h-11 min-w-0 flex-1 rounded-lg border border-field bg-white px-3 py-2 text-lg font-bold text-ink focus:border-primary focus:outline-none">
						<button type="submit" data-bind="disable: saving"
							class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary-dark disabled:cursor-default disabled:opacity-50">保存</button>
						<button type="button" data-bind="click: cancelRename, disable: saving"
							class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-field bg-white px-4 text-sm text-ink hover:bg-canvas disabled:cursor-default disabled:opacity-50">キャンセル</button>
					</div>
					<p role="alert" data-bind="visible: renameError, text: renameError" style="display: none;" class="m-0 text-[12.5px] text-danger"></p>
				</form>
			<!-- /ko -->
			<p class="m-0 text-[13px] text-muted"><span data-bind="text: total"></span>名 ・ 最終更新 <span data-bind="text: updatedLabel"></span></p>
		</div>
		<div class="flex flex-wrap gap-2.5">
			<button type="button" data-bind="click: deleteBoard, disable: saving"
				class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-field bg-white px-4 text-sm text-danger hover:bg-danger-tint disabled:cursor-default disabled:opacity-50">ボードを削除</button>
			<?php // 新しい応募はほとんど最初のステージ（書類選考）から始まるので、上部のボタンはそこに入れる ?>
			<button type="button" data-bind="click: function () { openCreate(firstColumn); }, disable: saving"
				class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-[18px] text-sm font-bold text-white hover:bg-primary-dark disabled:cursor-default disabled:opacity-50">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>カードを追加
			</button>
		</div>
	</div>

	<?php // 列を狭くするとカードの文字が読めなくなるため、狭い画面では列を縮めずに横スクロールにする ?>
	<div class="overflow-x-auto pb-1.5">
		<?php // 列の数は statuses テーブルの行数で決まるので、クラスに固定せずに数を入れる ?>
		<div class="grid items-start gap-4" style="grid-template-columns: repeat(<?php echo count($statuses); ?>, minmax(260px, 1fr));">
			<?php foreach ($statuses as $status): ?>
				<section aria-label="<?php echo $status['title']; ?>" data-status-id="<?php echo $status['id']; ?>"
					data-bind="with: columns[<?php echo $status['id']; ?>]">
					<?php // カードの上だけでなく列の空いているところにも落とせるよう、列の全体でドロップを受ける。
						// dragenter も取り消すのは、別の要素に入ってすぐ離すと、次の dragover が来る前で drop が起きないため ?>
					<div data-bind="event: { dragenter: $root.dragOver, dragover: $root.dragOver, dragleave: $root.dragLeave, drop: $root.drop },
							css: { 'border-primary-line bg-primary-tint': $root.isDropColumn($data), 'border-line bg-column': !$root.isDropColumn($data) }"
						class="flex min-h-[560px] flex-col gap-2.5 rounded-xl border p-3 transition-colors">
						<div class="flex items-center justify-between px-1 pt-0.5 pb-1">
							<h2 class="m-0 flex items-center gap-2 text-sm font-bold <?php echo $status['heading']; ?>">
								<span aria-hidden="true" class="inline-block size-2.5 rounded-full <?php echo $status['dot']; ?>"></span><?php echo $status['title']; ?>
							</h2>
							<span data-bind="text: cards().length"
								class="min-w-3 rounded-full border border-line bg-white px-[9px] py-px text-center text-xs font-bold text-muted"></span>
						</div>

						<ul data-bind="foreach: cards" class="m-0 flex list-none flex-col gap-2.5 p-0">
							<li data-bind="attr: { draggable: $root.saving() ? 'false' : 'true' },
									event: { dragstart: $root.dragStart, dragend: $root.dragEnd },
									css: {
										'opacity-40': $root.draggingCard() === $data,
										'before:absolute before:inset-x-0 before:-top-[7px] before:h-[3px] before:rounded before:bg-primary': $root.isDropAt($parent, $index()),
										'after:absolute after:inset-x-0 after:-bottom-[7px] after:h-[3px] after:rounded after:bg-primary': $index() === $parent.cards().length - 1 && $root.isDropAt($parent, $index() + 1)
									}"
								class="relative flex cursor-grab flex-col gap-1.5 rounded-[10px] border border-line bg-white px-3 pt-3 pb-2.5 shadow-[0_1px_2px_rgba(16,24,40,0.06)]">
								<div class="flex items-start justify-between gap-2">
									<h3 data-bind="text: title" class="m-0 text-sm leading-[1.45] font-bold break-all"></h3>
									<button type="button" data-bind="click: $root.openEdit, disable: $root.saving, attr: { 'aria-label': title() + ' を編集' }"
										class="-mt-1 -mr-1 inline-flex size-7 flex-none cursor-pointer items-center justify-center rounded-md text-muted hover:bg-canvas disabled:cursor-default disabled:opacity-50">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
									</button>
								</div>
								<p data-bind="visible: memo, text: memo" class="m-0 text-[12.5px] leading-[1.65] break-all whitespace-pre-line text-ink-soft"></p>
								<div class="flex items-center gap-[5px] pt-0.5 text-[11.5px] text-muted">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
									<span data-bind="text: updatedLabel"></span>
								</div>
							</li>
						</ul>

						<div data-bind="visible: cards().length === 0" style="display: none;"
							class="rounded-[10px] border-[1.5px] border-dashed border-faint px-3 py-[22px] text-center text-[12.5px] text-muted">ここにカードをドロップ</div>

						<button type="button" data-bind="click: $root.openCreate, disable: $root.saving"
							class="flex min-h-10 cursor-pointer items-center gap-1.5 rounded-lg px-2 py-2.5 text-left text-[13px] text-ink-soft hover:bg-black/5 disabled:cursor-default disabled:opacity-50">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>カードを追加
						</button>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
	</div>

	<dialog aria-labelledby="card-dialog-title" data-bind="modal: dialog.open"
		class="m-auto w-[calc(100%-32px)] outline-none max-w-[560px] rounded-[14px] bg-white p-0 text-ink shadow-[0_20px_48px_rgba(16,24,40,0.28)]">
		<form data-bind="submit: saveDialog" class="flex flex-col">
			<div class="flex items-center justify-between border-b border-line-soft pt-[18px] pr-5 pb-3.5 pl-6">
				<h2 id="card-dialog-title" data-bind="text: dialog.heading" class="m-0 text-lg font-bold"></h2>
				<button type="button" aria-label="閉じる" data-bind="click: closeDialog"
					class="inline-flex size-10 cursor-pointer items-center justify-center rounded-lg text-muted hover:bg-canvas">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>
				</button>
			</div>

			<div class="flex flex-col gap-[18px] px-6 py-5">
				<p role="alert" data-bind="visible: dialog.error, text: dialog.error" style="display: none;"
					class="m-0 rounded-lg border border-danger-line bg-danger-tint px-3 py-2.5 text-[13.5px] whitespace-pre-line text-danger"></p>
				<div class="flex flex-col gap-1.5">
					<div class="flex items-baseline justify-between">
						<label for="card-title" class="text-[13px] font-bold">応募者名<span class="text-danger"> *</span></label>
						<span data-bind="text: dialog.titleCounter" class="text-xs text-muted"></span>
					</div>
					<input type="text" id="card-title" maxlength="<?php echo Model_Card::TITLE_MAX_LENGTH; ?>" placeholder="例：山田 太郎" autofocus
						data-bind="textInput: dialog.title"
						class="min-h-[46px] w-full rounded-lg border border-field px-3 py-2.5 text-[15px] text-ink focus:border-primary focus:outline-none">
				</div>
				<div class="flex flex-col gap-1.5">
					<span data-bind="text: dialog.statusLabel" class="text-[13px] font-bold"></span>
					<div class="flex flex-wrap items-center gap-2.5">
						<span data-bind="css: dialog.status().chip" class="inline-flex items-center gap-1.5 rounded-full px-3 py-[5px] text-[13px] font-bold">
							<span aria-hidden="true" data-bind="css: dialog.status().dot" class="inline-block size-2 rounded-full"></span>
							<span data-bind="text: dialog.status().title"></span>
						</span>
						<span data-bind="text: dialog.statusHint"
							class="text-[12.5px] text-muted"></span>
					</div>
				</div>
				<div class="flex flex-col gap-1.5">
					<label for="card-memo" class="text-[13px] font-bold">メモ</label>
					<textarea id="card-memo" rows="5" maxlength="<?php echo Model_Card::MEMO_MAX_LENGTH; ?>" placeholder="面接日程や評価のメモなど"
						data-bind="textInput: dialog.memo"
						class="w-full resize-y rounded-lg border border-field px-3 py-2.5 text-sm leading-[1.7] text-ink focus:border-primary focus:outline-none"></textarea>
				</div>
				<!-- ko if: dialog.card() -->
					<p class="m-0 text-xs text-muted">最終更新 <span data-bind="text: dialog.card().updatedLabel"></span></p>
				<!-- /ko -->
			</div>

			<div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-soft px-6 pt-3.5 pb-5">
				<div>
					<!-- ko if: dialog.card() -->
						<button type="button" data-bind="click: deleteCard, disable: saving"
							class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg px-1 text-sm text-danger hover:underline disabled:cursor-default disabled:opacity-50">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M6 6l1 14h10l1-14"></path></svg>カードを削除
						</button>
					<!-- /ko -->
				</div>
				<div class="flex gap-2.5">
					<button type="button" data-bind="click: closeDialog"
						class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-field bg-white px-[18px] text-sm text-ink hover:bg-canvas">キャンセル</button>
					<button type="submit" data-bind="disable: saving, text: dialog.submitLabel"
						class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-[22px] text-sm font-bold text-white hover:bg-primary-dark disabled:cursor-default disabled:opacity-50"></button>
				</div>
			</div>
		</form>
	</dialog>

	<?php echo render('_confirm_dialog'); ?>
	<?php echo render('_toast'); ?>
</main>

<?php echo Asset::js(array('knockout-3.5.1.js', 'ui.js', 'board.js')); ?>
