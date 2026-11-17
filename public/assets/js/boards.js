(function () {
	'use strict';

	const root = document.getElementById('boards');
	const apiUrl = root.getAttribute('data-api-url');
	const boardUrl = root.getAttribute('data-board-url');
	// 選考ステージの名前と色（id・title・dot）。サーバーが出したものを使い、JSに名前を書かない
	const statuses = JSON.parse(root.getAttribute('data-statuses'));
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

	function BoardItem(data) {
		const self = this;

		self.id = data.id;
		self.url = boardUrl + '/' + data.id;
		self.title = ko.observable(data.title);
		self.cardCount = data.card_count;
		self.updatedAt = ko.observable(data.updated_at);
		self.updatedLabel = ko.pureComputed(function () {
			return KanbanUi.formatDateTime(self.updatedAt());
		});
		self.editing = ko.observable(false);
		// 保存に失敗したとき、表示中の名前を変えずに入力だけを残すため、title とは別に持つ
		self.draftTitle = ko.observable('');
		self.renameError = ko.observable('');

		const countById = {};
		data.status_counts.forEach(function (item) {
			countById[item.status_id] = item.count;
		});
		// 件数が0のステージもサーバーは返さないので、全ステージを並べて0で埋める
		self.counts = statuses.map(function (status) {
			return { title: status.title, dot: status.dot, count: countById[status.id] || 0 };
		});
		// 0件のステージを並べると、バーに幅0の区切りだけが残るため外す
		self.segments = self.counts.filter(function (item) { return item.count > 0; });
		self.barLabel = self.counts.map(function (item) { return item.title + ' ' + item.count + '名'; }).join('、');
	}

	function BoardsViewModel() {
		const self = this;

		self.boards = ko.observableArray([]);
		self.loaded = ko.observable(false);
		self.newTitle = ko.observable('');
		self.createError = ko.observable('');
		self.saving = ko.observable(false);
		self.confirmation = new KanbanUi.Confirmation();
		self.toast = new KanbanUi.Toast();

		function apiUrlOf(board) {
			return apiUrl + '/' + board.id;
		}

		/**
		 * 保存中に別の操作を受け付けると、画面とサーバーの状態がずれるため、1つずつ送る
		 */
		function save(request, onSuccess, onFailure) {
			if (self.saving()) {
				return;
			}
			self.saving(true);
			self.toast.close();

			request()
				.then(onSuccess)
				.catch(function (error) { onFailure(error.message); })
				.then(function () { self.saving(false); });
		}

		self.load = function () {
			callApi('GET', apiUrl)
				.then(function (data) {
					self.boards(data.boards.map(function (board) { return new BoardItem(board); }));
					self.loaded(true);
				})
				.catch(function (error) { self.toast.show(error.message); });
		};

		self.addBoard = function () {
			self.createError('');
			save(
				function () { return callApi('POST', apiUrl, { title: self.newTitle() }); },
				function (data) {
					// 一覧は更新日時の新しい順なので、作ったボードは先頭に入れる
					self.boards.unshift(new BoardItem(data.board));
					self.newTitle('');
				},
				self.createError
			);
		};

		self.startEdit = function (board) {
			board.draftTitle(board.title());
			board.renameError('');
			board.editing(true);
		};

		self.cancelEdit = function (board) {
			board.editing(false);
		};

		self.saveTitle = function (board) {
			board.renameError('');
			save(
				function () { return callApi('PUT', apiUrlOf(board), { title: board.draftTitle() }); },
				function (data) {
					board.title(data.board.title);
					board.updatedAt(data.board.updated_at);
					board.editing(false);
					// 名前を変えると更新日時が新しくなり、再読み込みすると先頭に来るので、それに合わせる
					self.boards.remove(board);
					self.boards.unshift(board);
				},
				board.renameError
			);
		};

		self.deleteBoard = function (board) {
			if (self.saving()) {
				return;
			}
			self.confirmation.ask(
				'ボード「' + board.title() + '」を削除しますか？',
				KanbanUi.boardDeleteMessage(board.cardCount),
				function () {
					save(
						function () { return callApi('DELETE', apiUrlOf(board)); },
						function () { self.boards.remove(board); },
						self.toast.show
					);
				}
			);
		};
	}

	const viewModel = new BoardsViewModel();
	ko.applyBindings(viewModel, root);
	viewModel.load();
})();
