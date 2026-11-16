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

	function BoardsViewModel() {
		var self = this;

		self.boards = ko.observableArray([]);
		self.loaded = ko.observable(false);
		self.newTitle = ko.observable('');
		self.errorMessage = ko.observable('');
		self.saving = ko.observable(false);

		self.load = function () {
			callApi('GET', apiUrl)
				.then(function (data) {
					self.boards(data.boards);
					self.loaded(true);
				})
				.catch(function (error) { self.errorMessage(error.message); });
		};

		self.addBoard = function () {
			if (self.saving()) {
				return;
			}
			self.saving(true);
			self.errorMessage('');

			callApi('POST', apiUrl, { title: self.newTitle() })
				.then(function (data) {
					// 一覧は更新日時の新しい順なので、作ったボードは先頭に入れる
					self.boards.unshift(data.board);
					self.newTitle('');
				})
				.catch(function (error) { self.errorMessage(error.message); })
				.then(function () { self.saving(false); });
		};
	}

	var viewModel = new BoardsViewModel();
	ko.applyBindings(viewModel, root);
	viewModel.load();
})();
