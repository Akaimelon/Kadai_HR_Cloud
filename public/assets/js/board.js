(function () {
	'use strict';

	const root = document.getElementById('board');
	const apiUrl = root.getAttribute('data-api-url');
	const cardApiUrl = root.getAttribute('data-card-api-url');
	const boardApiUrl = root.getAttribute('data-board-api-url');
	const boardsUrl = root.getAttribute('data-boards-url');
	// 選考ステージの名前と色（id・title・dot・chip）。モーダルに出すため、サーバーが出したものを使う
	const statuses = JSON.parse(root.getAttribute('data-statuses'));

	// 応募者名の上限。入力チェックと同じ値をサーバーから受け取り、JSに数を書かない
	const cardTitleMax = Number(root.getAttribute('data-card-title-max'));
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
		const self = this;

		self.id = data.id;
		self.statusId = data.status_id;
		self.title = ko.observable(data.title);
		self.memo = ko.observable(data.memo);
		self.updatedAt = ko.observable(data.updated_at);
		self.updatedLabel = ko.pureComputed(function () {
			return KanbanUi.formatDateTime(self.updatedAt());
		});
	}

	function Column(status) {
		this.statusId = status.id;
		this.status = status;
		this.cards = ko.observableArray([]);
	}

	/**
	 * カードの作成・編集のモーダルの状態。
	 * card が null なら作成、カードが入っていればそのカードの編集
	 */
	function CardDialog(firstColumn) {
		const self = this;

		self.open = ko.observable(false);
		self.card = ko.observable(null);
		self.column = ko.observable(firstColumn);
		// 保存に失敗したとき、カードの表示を変えずに入力だけを残すため、カードとは別に持つ
		self.title = ko.observable('');
		self.memo = ko.observable('');
		self.error = ko.observable('');
		self.status = ko.pureComputed(function () {
			return self.column().status;
		});

		// 作成と編集で同じモーダルを使い、文言だけを切り替える
		self.isEdit = ko.pureComputed(function () {
			return self.card() !== null;
		});
		self.heading = ko.pureComputed(function () {
			return self.isEdit() ? 'カードを編集' : 'カードを作成';
		});
		self.submitLabel = ko.pureComputed(function () {
			return self.isEdit() ? '保存' : '作成';
		});
		self.statusLabel = ko.pureComputed(function () {
			return self.isEdit() ? '選考ステージ' : '追加する列';
		});
		// 選考ステージはモーダルでは変えられないので、変え方と、どの列に入るかを添える
		self.statusHint = ko.pureComputed(function () {
			return self.isEdit()
				? 'ボード上でカードをドラッグして変更します'
				: '押した「カードを追加」の列に入ります。上部のボタンからは' + firstColumn.status.title + 'に入ります';
		});
		self.titleCounter = ko.pureComputed(function () {
			return self.title().length + ' / ' + cardTitleMax;
		});
	}

	function BoardViewModel() {
		const self = this;

		// 列はサーバーが出したHTMLにあるので、その status_id から列を作る
		self.columns = {};
		statuses.forEach(function (status) {
			self.columns[status.id] = new Column(status);
		});
		self.firstColumn = self.columns[statuses[0].id];

		self.title = ko.observable(root.getAttribute('data-title'));
		self.updatedAt = ko.observable(root.getAttribute('data-updated-at'));
		self.updatedLabel = ko.pureComputed(function () {
			return KanbanUi.formatDateTime(self.updatedAt());
		});
		self.total = ko.pureComputed(function () {
			return statuses.reduce(function (sum, status) {
				return sum + self.columns[status.id].cards().length;
			}, 0);
		});

		self.saving = ko.observable(false);
		self.dialog = new CardDialog(self.firstColumn);
		self.confirmation = new KanbanUi.Confirmation();
		self.toast = new KanbanUi.Toast();

		// カードやボード名を変えるとボードの更新日時も新しくなる（設計書4章）ので、成功したら見出しの表示も進める
		const save = KanbanUi.createSave(self.saving, self.toast, function () {
			self.updatedAt(KanbanUi.now());
		});

		self.load = function () {
			callApi('GET', apiUrl)
				.then(function (data) {
					// サーバーは列ごと・上から順に返すので、順に積めば列の中の並びも保たれる
					data.cards.forEach(function (card) {
						self.columns[card.status_id].cards.push(new CardItem(card));
					});
				})
				.catch(function (error) { self.toast.show(error.message); });
		};

		// ---- ボード名の変更・ボードの削除 ----

		self.renaming = ko.observable(false);
		self.draftTitle = ko.observable('');
		self.renameError = ko.observable('');

		self.startRename = function () {
			self.draftTitle(self.title());
			self.renameError('');
			self.renaming(true);
		};

		self.cancelRename = function () {
			self.renaming(false);
		};

		self.saveTitle = function () {
			self.renameError('');
			save(
				function () { return callApi('PUT', boardApiUrl, { title: self.draftTitle() }); },
				function (data) {
					self.title(data.board.title);
					document.title = data.board.title + ' | 採用カンバン';
					self.renaming(false);
				},
				self.renameError
			);
		};

		self.deleteBoard = function () {
			if (self.saving()) {
				return;
			}
			self.confirmation.ask(
				'ボード「' + self.title() + '」を削除しますか？',
				KanbanUi.boardDeleteMessage(self.total()),
				function () {
					save(
						function () { return callApi('DELETE', boardApiUrl); },
						function () { window.location.href = boardsUrl; },
						self.toast.show
					);
				}
			);
		};

		// ---- カードの作成・編集・削除（モーダル） ----

		function cardUrl(card) {
			return cardApiUrl + '/' + card.id;
		}

		self.openCreate = function (column) {
			self.dialog.card(null);
			self.dialog.column(column);
			self.dialog.title('');
			self.dialog.memo('');
			self.dialog.error('');
			self.dialog.open(true);
		};

		self.openEdit = function (card) {
			self.dialog.card(card);
			self.dialog.column(self.columns[card.statusId]);
			self.dialog.title(card.title());
			self.dialog.memo(card.memo() || '');
			self.dialog.error('');
			self.dialog.open(true);
		};

		self.closeDialog = function () {
			self.dialog.open(false);
		};

		self.saveDialog = function () {
			const card = self.dialog.card();
			self.dialog.error('');

			if (card === null) {
				const column = self.dialog.column();
				save(
					function () {
						return callApi('POST', apiUrl, {
							title: self.dialog.title(),
							memo: self.dialog.memo(),
							status_id: column.statusId
						});
					},
					function (data) {
						// サーバーは列の一番下に作るので、画面でも末尾に足す
						column.cards.push(new CardItem(data.card));
						self.dialog.open(false);
					},
					self.dialog.error
				);
				return;
			}

			save(
				function () {
					return callApi('PUT', cardUrl(card), { title: self.dialog.title(), memo: self.dialog.memo() });
				},
				function (data) {
					// 前後の空白を除くなど、サーバーが整えた値を表示する
					card.title(data.card.title);
					card.memo(data.card.memo);
					card.updatedAt(data.card.updated_at);
					self.dialog.open(false);
				},
				self.dialog.error
			);
		};

		self.deleteCard = function () {
			const card = self.dialog.card();
			if (self.saving()) {
				return;
			}
			// 確認ダイアログはカードのモーダルの上に重ねて開き、キャンセルしたらモーダルに戻れるようにする
			self.confirmation.ask(
				'カード「' + card.title() + '」を削除しますか？',
				'メモも含めて削除され、元に戻せません。',
				function () {
					self.dialog.open(false);
					save(
						function () { return callApi('DELETE', cardUrl(card)); },
						function () { self.columns[card.statusId].cards.remove(card); },
						self.toast.show
					);
				}
			);
		};

		// ---- ドラッグ&ドロップ ----

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
				function (data) { card.updatedAt(data.card.updated_at); },
				function (message) {
					place(fromColumn, fromIndex);
					self.toast.show(message.replace(/。$/, '') + '。カードを元の位置に戻しました。');
				}
			);
		}
	}

	const viewModel = new BoardViewModel();
	ko.applyBindings(viewModel, root);
	viewModel.load();
})();
