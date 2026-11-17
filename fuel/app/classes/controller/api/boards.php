<?php
class Controller_Api_Boards extends Controller_Api_Base
{
	public function get_index()
	{
		$counts = Model_Board::count_cards_by_status(Guard::user_id());
		$boards = array();
		foreach (Model_Board::find_by_owner(Guard::user_id()) as $board)
		{
			$board['status_counts'] = \Arr::get($counts, $board['id'], array());
			$boards[] = $this->to_json($board);
		}

		// 配列のまま返すと、0件のとき Controller_Rest が 204（本文なし）にするため、boards に包む
		return $this->response(array('boards' => $boards));
	}

	public function post_index()
	{
		$title = $this->validated_title();
		$id = Model_Board::create(Guard::user_id(), $title);

		// 画面に更新日時を出すため、DBに入った updated_at ごと返す
		$board = Model_Board::find($id, Guard::user_id());
		$board['card_count'] = 0;
		$board['status_counts'] = array();

		return $this->response(array('board' => $this->to_json($board)), 201);
	}

	/**
	 * @param string $id
	 */
	public function put_item($id)
	{
		$this->ensure_board_accessible($id);
		$title = $this->validated_title();
		Model_Board::rename($id, Guard::user_id(), $title);
		$board = Model_Board::find($id, Guard::user_id());

		return $this->response(array(
			'board' => array('id' => (int) $board['id'], 'title' => $board['title'], 'updated_at' => $board['updated_at']),
		));
	}

	/**
	 * @param string $id
	 */
	public function delete_item($id)
	{
		$this->ensure_board_accessible($id);
		Model_Board::delete($id, Guard::user_id());

		return $this->response(array(), 204);
	}

	/**
	 * 作成と名前変更で、ボード名の決まりを同じにするため1か所にまとめる
	 *
	 * @return string  前後の空白を除いたボード名
	 * @throws HttpJsonException  入力エラーのとき（422）
	 */
	protected function validated_title()
	{
		$val = \Validation::forge();
		$val->add('title', 'ボード名')
			->add_rule('trim')
			->add_rule('required')
			->add_rule('max_length', 100);
			->add_rule('max_length', Model_Board::TITLE_MAX_LENGTH);
		$val->set_message('required', ':labelを入力してください');
		$val->set_message('max_length', ':labelは:param:1文字以内で入力してください');

		if ( ! $val->run(array('title' => (string) \Input::json('title', ''))))
		{
			throw new HttpJsonException(422, implode("\n", $val->error_message()));
		}

		return $val->validated('title');
	}

	/**
	 * DBの値は文字列で返ってくるので、JSで数として扱えるように型を揃える
	 *
	 * status_counts を {選考ステージID: 件数} のオブジェクトにしないのは、
	 * PHPの配列が空のときに、JSONではオブジェクトではなく配列になってしまうため
	 *
	 * @param  array $board  id・title・updated_at・card_count・status_counts（選考ステージIDをキーにした件数）
	 * @return array
	 */
	protected function to_json(array $board)
	{
		$status_counts = array();
		foreach ($board['status_counts'] as $status_id => $count)
		{
			$status_counts[] = array('status_id' => (int) $status_id, 'count' => (int) $count);
		}

		return array(
			'id'            => (int) $board['id'],
			'title'         => $board['title'],
			'updated_at'    => $board['updated_at'],
			'card_count'    => (int) $board['card_count'],
			'status_counts' => $status_counts,
		);
	}
}
