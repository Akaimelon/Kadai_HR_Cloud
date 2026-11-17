/**
 * ボード一覧とカンバン画面で共通に使う、画面の部品。
 * knockout より後、boards.js・board.js より前に読み込む。
 */
(function () {
	'use strict';

	/**
	 * <dialog> の開閉を observable で切り替える。
	 * showModal() で開くのは、Esc で閉じる・後ろの画面を操作させない・後ろを暗くする、をブラウザに任せるため。
	 * Esc で閉じたときも observable を false に戻し、画面の状態とずれないようにする
	 */
	ko.bindingHandlers.modal = {
		init: function (element, valueAccessor) {
			element.addEventListener('close', function () {
				const open = valueAccessor();
				if (open()) {
					open(false);
				}
			});
		},
		update: function (element, valueAccessor) {
			const open = ko.unwrap(valueAccessor());
			if (open && !element.open) {
				element.showModal();
			} else if (!open && element.open) {
				element.close();
			}
		}
	};

	/**
	 * 削除の確認ダイアログ（views/_confirm_dialog.php）の状態。
	 * window.confirm を使わないのは、見た目を画面に揃えるためと、
	 * ブラウザの自動操作（Claude in Chrome）が本物のダイアログで止まらないようにするため
	 */
	function Confirmation() {
		const self = this;
		let onAccept = null;

		self.open = ko.observable(false);
		self.title = ko.observable('');
		self.message = ko.observable('');

		self.ask = function (title, message, accept) {
			self.title(title);
			self.message(message);
			onAccept = accept;
			self.open(true);
		};

		self.accept = function () {
			self.open(false);
			onAccept();
		};

		self.cancel = function () {
			self.open(false);
		};
	}

	/**
	 * 画面下に出す通知（views/_toast.php）の状態。
	 * 移動や削除など、入力欄のない操作の失敗を知らせるために使う
	 */
	function Toast() {
		const self = this;

		self.message = ko.observable('');

		self.show = function (message) {
			self.message(message);
		};

		self.close = function () {
			self.message('');
		};
	}

	window.KanbanUi = {
		Confirmation: Confirmation,
		Toast: Toast,

		/**
		 * ボードの削除の確認に出す説明。ボード一覧とカンバン画面で同じ文にするため、ここで作る
		 */
		boardDeleteMessage: function (cardCount) {
			// 0枚のときに「カード0枚も一緒に」と出すと不自然なため、文を変える
			return cardCount > 0
				? 'このボードのカード' + cardCount + '枚も一緒に削除され、元に戻せません。'
				: '削除すると元に戻せません。';
		},

		/**
		 * 「2026-11-12 21:40:00」を「11/12 21:40」にする。
		 * 年度ごとにボードを分ける使い方なので、年は出さない
		 */
		formatDateTime: function (value) {
			return value.slice(5, 7) + '/' + value.slice(8, 10) + ' ' + value.slice(11, 16);
		},

		/**
		 * 今の日時を、サーバーが返すのと同じ「2026-11-12 21:40:00」の形にする。
		 * 保存に成功したとき、再読み込みせずに更新日時の表示を進めるため
		 */
		now: function () {
			const d = new Date();
			function pad(n) { return (n < 10 ? '0' : '') + n; }
			return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' +
				pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
		}
	};
})();
