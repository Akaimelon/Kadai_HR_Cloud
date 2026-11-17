<?php // 中身は public/assets/js/ui.js の Toast。ビューモデルの toast に結び付ける ?>
<div role="status" data-bind="visible: toast.message" style="display: none;"
	class="fixed bottom-7 left-1/2 z-10 flex w-[min(560px,calc(100%-32px))] -translate-x-1/2 items-center gap-3 rounded-[10px] bg-ink py-3 pr-3 pl-4 text-sm text-white shadow-[0_8px_24px_rgba(16,24,40,0.24)]">
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="flex-none text-warning"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
	<span data-bind="text: toast.message" class="flex-1 leading-normal"></span>
	<button type="button" data-bind="click: toast.close"
		class="min-h-9 cursor-pointer rounded-md border border-white/35 bg-transparent px-3 text-[13px] text-white hover:bg-white/10">閉じる</button>
</div>
