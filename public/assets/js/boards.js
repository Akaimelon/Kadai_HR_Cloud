(function () {
	'use strict';

	var root = document.getElementById('boards');
	var apiUrl = root.getAttribute('data-api-url');
	var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

	/**
	 * 失敗はどれも、画面にそのまま出せるメッセージで reject する。
	 * 呼び出し側が、通信エラーか入力エラーかを区別せずに error.message を表示できるようにするため
	 */
	function callApi(method, url, body) {
		var options = {
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

	function BoardItem(data) {
		this.id = data.id;
		this.title = ko.observable(data.title);
		this.cardCount = data.card_count;
		this.editing = ko.observable(false);
		// 保存に失敗したとき、表示中の名前を変えずに入力だけを残すため、title とは別に持つ
		this.draftTitle = ko.observable('');
	}

	function BoardsViewModel() {
		var self = this;

		self.boards = ko.observableArray([]);
		self.loaded = ko.observable(false);
		self.newTitle = ko.observable('');
		self.errorMessage = ko.observable('');
		self.saving = ko.observable(false);

		function boardUrl(board) {
			return apiUrl + '/' + board.id;
		}

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
					self.boards(data.boards.map(function (board) { return new BoardItem(board); }));
					self.loaded(true);
				})
				.catch(function (error) { self.errorMessage(error.message); });
		};

		self.addBoard = function () {
			save(
				function () { return callApi('POST', apiUrl, { title: self.newTitle() }); },
				function (data) {
					// 一覧は更新日時の新しい順なので、作ったボードは先頭に入れる
					self.boards.unshift(new BoardItem(data.board));
					self.newTitle('');
				}
			);
		};

		self.startEdit = function (board) {
			board.draftTitle(board.title());
			board.editing(true);
		};

		self.cancelEdit = function (board) {
			board.editing(false);
		};

		self.saveTitle = function (board) {
			save(
				function () { return callApi('PUT', boardUrl(board), { title: board.draftTitle() }); },
				function (data) {
					board.title(data.board.title);
					board.editing(false);
					// 名前を変えると更新日時が新しくなり、再読み込みすると先頭に来るので、それに合わせる
					self.boards.remove(board);
					self.boards.unshift(board);
				}
			);
		};

		self.deleteBoard = function (board) {
			var message = '「' + board.title() + '」を削除します。応募' + board.cardCount + '件も削除されます。よろしいですか？';
			if (self.saving() || !window.confirm(message)) {
				return;
			}
			save(
				function () { return callApi('DELETE', boardUrl(board)); },
				function () { self.boards.remove(board); }
			);
		};
	}

	var viewModel = new BoardsViewModel();
	ko.applyBindings(viewModel, root);
	viewModel.load();
})();
