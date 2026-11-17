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
		function save(request, onSuccess) {
			if (self.saving()) {
				return;
			}
			self.saving(true);
			self.errorMessage('');

			request()
				.then(onSuccess)
				.catch(function (error) { self.errorMessage(error.message); })
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
	}

	const viewModel = new BoardViewModel();
	ko.applyBindings(viewModel, root);
	viewModel.load();
})();
