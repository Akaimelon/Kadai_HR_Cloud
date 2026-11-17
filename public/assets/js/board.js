(function () {
	'use strict';

	const root = document.getElementById('board');
	const apiUrl = root.getAttribute('data-api-url');
	const cardApiUrl = root.getAttribute('data-card-api-url');
	const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

	/**
	 * 失敗はどれも、画面にそのまま出せるメッセージで reject する。
	 * 呼び出し側が、通信エラーか入力エラーかを区別せずに error.message を表示できるようにするため
	 */
	function callApi(method, url, body) {
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

	function CardItem(data) {
		this.id = data.id;
		this.statusId = data.status_id;
		this.title = ko.observable(data.title);
		this.memo = ko.observable(data.memo);
		this.editing = ko.observable(false);
		// 保存に失敗したとき、表示中の内容を変えずに入力だけを残すため、title・memo とは別に持つ
		this.draftTitle = ko.observable('');
		this.draftMemo = ko.observable('');
		// created_at は「2026-11-17 12:34:56」の形なので、先頭10文字が日付になる
		this.createdOn = data.created_at.slice(0, 10).replace(/-/g, '/');
	}

	function Column(statusId) {
		this.statusId = statusId;
		this.cards = ko.observableArray([]);
		this.adding = ko.observable(false);
		this.newTitle = ko.observable('');
		this.newMemo = ko.observable('');
	}

	function BoardViewModel() {
		const self = this;

		// 列はサーバーが出したHTMLにあるので、その status_id から列を作る
		self.columns = {};
		Array.prototype.forEach.call(root.querySelectorAll('[data-status-id]'), function (section) {
			const statusId = Number(section.getAttribute('data-status-id'));
			self.columns[statusId] = new Column(statusId);
		});

		self.errorMessage = ko.observable('');
		self.saving = ko.observable(false);

		/**
		 * 保存中に別の操作を受け付けると、画面とサーバーの状態がずれるため、1つずつ送る
		 */
		function save(request, onSuccess, onFailure) {
			if (self.saving()) {
				return;
			}
			self.saving(true);
			self.errorMessage('');

			request()
				.then(onSuccess)
				.catch(function (error) {
					if (onFailure) {
						onFailure();
					}
					self.errorMessage(error.message);
				})
				.then(function () { self.saving(false); });
		}

		self.load = function () {
			callApi('GET', apiUrl)
				.then(function (data) {
					// サーバーは列ごと・上から順に返すので、順に積めば列の中の並びも保たれる
					data.cards.forEach(function (card) {
						self.columns[card.status_id].cards.push(new CardItem(card));
					});
				})
				.catch(function (error) { self.errorMessage(error.message); });
		};

		self.startAdd = function (column) {
			column.newTitle('');
			column.newMemo('');
			column.adding(true);
		};

		self.cancelAdd = function (column) {
			column.adding(false);
		};

		self.addCard = function (column) {
			save(
				function () {
					return callApi('POST', apiUrl, {
						title: column.newTitle(),
						memo: column.newMemo(),
						status_id: column.statusId
					});
				},
				function (data) {
					// サーバーは列の一番下に作るので、画面でも末尾に足す
					column.cards.push(new CardItem(data.card));
					column.adding(false);
				}
			);
		};

		function cardUrl(card) {
			return cardApiUrl + '/' + card.id;
		}

		self.startEdit = function (card) {
			card.draftTitle(card.title());
			card.draftMemo(card.memo() || '');
			card.editing(true);
		};

		self.cancelEdit = function (card) {
			card.editing(false);
		};

		self.saveCard = function (card) {
			save(
				function () {
					return callApi('PUT', cardUrl(card), { title: card.draftTitle(), memo: card.draftMemo() });
				},
				function (data) {
					// 前後の空白を除くなど、サーバーが整えた値を表示する
					card.title(data.card.title);
					card.memo(data.card.memo);
					card.editing(false);
				}
			);
		};

		self.deleteCard = function (card) {
			const message = '「' + card.title() + '」のカードを削除します。よろしいですか？';
			if (self.saving() || !window.confirm(message)) {
				return;
			}
			save(
				function () { return callApi('DELETE', cardUrl(card)); },
				function () { self.columns[card.statusId].cards.remove(card); }
			);
		};

		self.draggingCard = ko.observable(null);
		// 差し込む位置の線を出すため、どの列の何番目の前に落とすかを持つ（index は移動前の並びで数える）
		self.dropTarget = ko.observable(null);

		self.isDropAt = function (column, index) {
			const target = self.dropTarget();
			return target !== null && target.column === column && target.index === index;
		};

		self.isDropColumn = function (column) {
			const target = self.dropTarget();
			return target !== null && target.column === column;
		};

		self.dragStart = function (card, event) {
			if (self.saving()) {
				return false;
			}
			// Firefox は何かデータを入れないとドラッグを始めないため
			event.dataTransfer.setData('text/plain', String(card.id));
			event.dataTransfer.effectAllowed = 'move';
			self.draggingCard(card);
			return true;
		};

		self.dragEnd = function () {
			self.draggingCard(null);
			self.dropTarget(null);
			return true;
		};

		/**
		 * カーソルより上に中心があるカードの枚数を、差し込む位置にする。
		 * カードの上でも列の空いているところでも、同じ計算で位置が決まるようにするため
		 */
		function dropIndex(columnElement, clientY) {
			const items = columnElement.querySelectorAll('ul > li');
			let index = 0;
			Array.prototype.forEach.call(items, function (item) {
				const rect = item.getBoundingClientRect();
				if (rect.top + rect.height / 2 < clientY) {
					index += 1;
				}
			});
			return index;
		}

		self.dragOver = function (column, event) {
			if (self.draggingCard() === null) {
				// カード以外（ファイルなど）のドラッグは受け付けない。true で既定の動きに任せ、ドロップ不可にする
				return true;
			}
			event.dataTransfer.dropEffect = 'move';
			const index = dropIndex(event.currentTarget, event.clientY);
			if (!self.isDropAt(column, index)) {
				self.dropTarget({ column: column, index: index });
			}
			return false;
		};

		self.dragLeave = function (column, event) {
			// 列の中のカードへ移っただけでも dragleave が来るので、列の外に出たときだけ線を消す
			if (!event.currentTarget.contains(event.relatedTarget) && self.isDropColumn(column)) {
				self.dropTarget(null);
			}
			return true;
		};

		self.drop = function (column, event) {
			const card = self.draggingCard();
			self.dragEnd();
			if (card === null) {
				return false;
			}
			// マウスを速く動かすと、最後の dragover の位置と離した位置がずれるため、離した位置で計算し直す
			moveCard(card, column, dropIndex(event.currentTarget, event.clientY));
			return false;
		};

		/**
		 * 通信を待たずに結果を見せるため、先に画面のカードを動かしてから保存する。
		 * 失敗したら元の列・位置に戻す（設計書6章）
		 */
		function moveCard(card, toColumn, index) {
			const fromColumn = self.columns[card.statusId];
			const fromIndex = fromColumn.cards.indexOf(card);
			// index は動かす前の並びで数えたので、同じ列で下へ動かすときは、自分が抜ける分を引く
			const toIndex = (fromColumn === toColumn && fromIndex < index) ? index - 1 : index;
			// save() も保存中は何もしないが、その前に画面を動かしてしまうので、ここでも止める
			if (self.saving() || (fromColumn === toColumn && fromIndex === toIndex)) {
				return;
			}

			function place(column, position) {
				self.columns[card.statusId].cards.remove(card);
				card.statusId = column.statusId;
				column.cards.splice(position, 0, card);
			}

			place(toColumn, toIndex);
			save(
				function () {
					return callApi('PUT', cardUrl(card) + '/move', { status_id: toColumn.statusId, sort_order: toIndex });
				},
				function () {},
				function () { place(fromColumn, fromIndex); }
			);
		}
	}

	const viewModel = new BoardViewModel();
	ko.applyBindings(viewModel, root);
	viewModel.load();
})();
