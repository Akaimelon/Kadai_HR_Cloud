<?php // 中身は public/assets/js/ui.js の Confirmation。ビューモデルの confirmation に結び付ける ?>
<dialog role="alertdialog" aria-labelledby="confirm-title" aria-describedby="confirm-message"
	data-bind="modal: confirmation.open"
	class="m-auto w-[calc(100%-32px)] outline-none max-w-[440px] rounded-[14px] bg-white p-6 text-ink shadow-[0_20px_48px_rgba(16,24,40,0.28)]">
	<div class="flex flex-col gap-4">
		<div class="flex size-11 items-center justify-center rounded-full bg-danger-soft text-danger">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M6 6l1 14h10l1-14"></path><path d="M10 11v5M14 11v5"></path></svg>
		</div>
		<div class="flex flex-col gap-2">
			<h2 id="confirm-title" data-bind="text: confirmation.title" class="m-0 text-lg leading-normal font-bold break-all"></h2>
			<p id="confirm-message" data-bind="text: confirmation.message" class="m-0 text-sm leading-[1.75] text-ink-soft"></p>
		</div>
		<div class="flex flex-wrap justify-end gap-2.5 pt-1">
			<?php // 誤って消さないよう、開いたときのフォーカスは「キャンセル」に置く ?>
			<button type="button" autofocus data-bind="click: confirmation.cancel"
				class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-field bg-white px-4.5 text-sm text-ink hover:bg-canvas">キャンセル</button>
			<button type="button" data-bind="click: confirmation.accept"
				class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-danger-strong px-5 text-sm font-bold text-white hover:bg-danger">削除する</button>
		</div>
	</div>
</dialog>
