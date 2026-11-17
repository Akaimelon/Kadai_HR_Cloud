<?php
class Controller_Api_Cards extends Controller_Api_Base
{
	/**
	 * @param string $board_id
	 */
	public function get_index($board_id)
	{
		$this->ensure_board_accessible($board_id);

		// 配列のまま返すと、0件のとき Controller_Rest が 204（本文なし）にするため、cards に包む
		return $this->response(array(
			'cards' => array_map(array($this, 'to_json'), Model_Card::find_by_board($board_id, Guard::user_id())),
		));
	}

	/**
	 * @param string $board_id
	 */
	public function post_index($board_id)
	{
		$this->ensure_board_accessible($board_id);

		$val = $this->card_validation();
		$val->add('status_id', '選考ステージ')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));
		$val->set_message('valid_string', ':labelが正しくありません。画面を再読み込みしてください');

		$input = array(
			'title'     => (string) \Input::json('title', ''),
			'memo'      => (string) \Input::json('memo', ''),
			'status_id' => (string) \Input::json('status_id', ''),
		);
		if ( ! $val->run($input))
		{
			throw new HttpJsonException(422, implode("\n", $val->error_message()));
		}

		$status_id = (int) $val->validated('status_id');
		if ( ! Model_Status::exists($status_id))
		{
			throw new HttpJsonException(422, '選考ステージが正しくありません。画面を再読み込みしてください');
		}

		$id = Model_Card::create($board_id, Guard::user_id(), $status_id, $val->validated('title'), $this->validated_memo($val));

		// 画面に更新日時を出すため、DBに入った updated_at ごと返す
		return $this->response(array(
			'card' => $this->to_json(Model_Card::find($id, Guard::user_id())),
		), 201);
	}

	/**
	 * @param string $id
	 */
	public function put_item($id)
	{
		$this->ensure_card_accessible($id);

		$val = $this->card_validation();
		$input = array(
			'title' => (string) \Input::json('title', ''),
			'memo'  => (string) \Input::json('memo', ''),
		);
		if ( ! $val->run($input))
		{
			throw new HttpJsonException(422, implode("\n", $val->error_message()));
		}

		Model_Card::update($id, Guard::user_id(), $val->validated('title'), $this->validated_memo($val));

		return $this->response(array(
			'card' => $this->to_json(Model_Card::find($id, Guard::user_id())),
		));
	}

	/**
	 * @param string $id
	 */
	public function put_move($id)
	{
		$this->ensure_card_accessible($id);

		$val = \Validation::forge();
		$val->add('status_id', '選考ステージ')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));
		$val->add('sort_order', '移動先の位置')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));
		// どちらも利用者が入力する値ではなく、画面が自動で送る値。おかしな値が届くのは画面が古いときなので、再読み込みを促す
		$val->set_message('required', ':labelが正しくありません。画面を再読み込みしてください');
		$val->set_message('valid_string', ':labelが正しくありません。画面を再読み込みしてください');

		$input = array(
			'status_id'  => (string) \Input::json('status_id', ''),
			'sort_order' => (string) \Input::json('sort_order', ''),
		);
		if ( ! $val->run($input))
		{
			throw new HttpJsonException(422, implode("\n", $val->error_message()));
		}

		$status_id = (int) $val->validated('status_id');
		if ( ! Model_Status::exists($status_id))
		{
			throw new HttpJsonException(422, '選考ステージが正しくありません。画面を再読み込みしてください');
		}

		\Kanban\CardMover::move(Model_Card::find($id, Guard::user_id()), $status_id, (int) $val->validated('sort_order'));

		return $this->response(array(
			'card' => $this->to_json(Model_Card::find($id, Guard::user_id())),
		));
	}

	/**
	 * @param string $id
	 */
	public function delete_item($id)
	{
		$this->ensure_card_accessible($id);
		Model_Card::delete($id, Guard::user_id());

		return $this->response(array(), 204);
	}

	/**
	 * 作成と編集で、応募者名とメモの決まりを同じにするため1か所にまとめる
	 *
	 * @return \Validation
	 */
	protected function card_validation()
	{
		$val = \Validation::forge();
		$val->add('title', '応募者名')
			->add_rule('trim')
			->add_rule('required')
			->add_rule('max_length', Model_Card::TITLE_MAX_LENGTH);
		$val->add('memo', 'メモ')
			->add_rule('trim')
			->add_rule('max_length', Model_Card::MEMO_MAX_LENGTH);
		$val->set_message('required', ':labelを入力してください');
		$val->set_message('max_length', ':labelは:param:1文字以内で入力してください');

		return $val;
	}

	/**
	 * メモは任意なので、空欄は「メモなし」として NULL で保存する
	 *
	 * @param  \Validation $val  run() が通ったもの
	 * @return string|null
	 */
	protected function validated_memo(\Validation $val)
	{
		return $val->validated('memo') === '' ? null : $val->validated('memo');
	}

	/**
	 * 他人のボードにも404を返すのは、そのIDのボードがあること自体を知らせないため（設計書5章）
	 *
	 * @param  string $board_id
	 * @throws HttpJsonException
	 */
	protected function ensure_board_accessible($board_id)
	{
		if ( ! Model_Board::can_access($board_id, Guard::user_id()))
		{
			throw new HttpJsonException(404, 'ボードが見つかりません。画面を再読み込みしてください');
		}
	}

	/**
	 * 存在しないカードと他人のカードを区別せず404にするのは、ボードと同じく、そのIDがあること自体を知らせないため
	 *
	 * @param  string $id
	 * @throws HttpJsonException
	 */
	protected function ensure_card_accessible($id)
	{
		if ( ! Model_Card::find($id, Guard::user_id()))
		{
			throw new HttpJsonException(404, 'カードが見つかりません。画面を再読み込みしてください');
		}
	}

	/**
	 * DBの値は文字列で返ってくるので、JSで数として扱えるように型を揃える
	 *
	 * @param  array $card
	 * @return array
	 */
	protected function to_json(array $card)
	{
		return array(
			'id'         => (int) $card['id'],
			'status_id'  => (int) $card['status_id'],
			'title'      => $card['title'],
			'memo'       => $card['memo'],
			'created_at' => $card['created_at'],
			'updated_at' => $card['updated_at'],
		);
	}
}
