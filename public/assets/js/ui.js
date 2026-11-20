/**
 * ボード一覧とカンバン画面で共通に使う、画面の部品と API の呼び出し。
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

	/**
	 * 失敗はどれも、画面にそのまま出せるメッセージで reject する。
	 * 呼び出し側が、通信エラーか入力エラーかを区別せずに error.message を表示できるようにするため
	 */
	function callApi(method, url, body) {
		const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
		const options = {
			method: method,
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken }
		};
		if (body !== undefined) {
			options.headers['Content-Type'] = 'application/json';
			options.body = JSON.stringify(body);
		}

		return fetch(url, options)
			.catch(function () {
				throw new Error('通信に失敗しました。ネットワークを確認してください');
			})
			.then(function (response) {
				return response.json()
					// サーバーのエラー画面（HTML）が返ってきたときも、同じメッセージで扱うため
					.catch(function () { return {}; })
					.then(function (data) {
						if (!response.ok) {
							throw new Error(data.error || 'エラーが発生しました。時間をおいてもう一度お試しください');
						}
						return data;
					});
			});
	}

	/**
	 * 保存の処理（save(request, onSuccess, onFailure)）を作る。
	 * 保存中に別の操作を受け付けると、画面とサーバーの状態がずれるため、1つずつ送る。
	 * afterSuccess は、画面ごとに成功のたびにしたいこと（カンバン画面の更新日時など）を渡す。応答の本文を受け取る
	 */
	function createSave(saving, toast, afterSuccess) {
		return function (request, onSuccess, onFailure) {
			if (saving()) {
				return;
			}
			saving(true);
			toast.close();

			// 失敗の処理は then の2つ目に渡し、保存の失敗だけを受け取る。
			// .catch にすると成功の処理の中の例外まで受け取り、保存できたのにカードを元の位置に戻してしまう。
			// 成功の処理の例外は握りつぶさず、コンソールに出す（finally は例外をそのまま先へ流す）
			request()
				.then(function (data) {
					onSuccess(data);
					if (afterSuccess) {
						afterSuccess(data);
					}
				}, function (error) { onFailure(error.message); })
				.finally(function () { saving(false); });
		};
	}

	window.KanbanUi = {
		Confirmation: Confirmation,
		Toast: Toast,
		callApi: callApi,
		createSave: createSave,

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
		}
	};
})();
