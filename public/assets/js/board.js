(function () {
	'use strict';

	const root = document.getElementById('board');
	const apiUrl = root.getAttribute('data-api-url');
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
		this.title = ko.observable(data.title);
		this.memo = ko.observable(data.memo);
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
	}

	const viewModel = new BoardViewModel();
	ko.applyBindings(viewModel, root);
	viewModel.load();
})();
