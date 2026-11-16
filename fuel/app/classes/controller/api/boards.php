<?php
class Controller_Api_Boards extends Controller_Api_Base
{
	public function get_index()
	{
		// 配列のまま返すと、0件のとき Controller_Rest が 204（本文なし）にするため、boards に包む
		return $this->response(array(
			'boards' => array_map(array($this, 'to_json'), Model_Board::find_by_owner(Guard::user_id())),
		));
	}

	public function post_index()
	{
		$val = \Validation::forge();
		$val->add('title', 'ボード名')
			->add_rule('trim')
			->add_rule('required')
			->add_rule('max_length', 100);
		$val->set_message('required', ':labelを入力してください');
		$val->set_message('max_length', ':labelは:param:1文字以内で入力してください');

		if ( ! $val->run(array('title' => (string) \Input::json('title', ''))))
		{
			return $this->response(array('error' => implode("\n", $val->error_message())), 422);
		}

		$title = $val->validated('title');
		$id = Model_Board::create(Guard::user_id(), $title);

		return $this->response(array(
			'board' => $this->to_json(array('id' => $id, 'title' => $title, 'card_count' => 0)),
		), 201);
	}

	/**
	 * DBの値は文字列で返ってくるので、JSで数として扱えるように型を揃える
	 *
	 * @param  array $board
	 * @return array
	 */
	protected function to_json(array $board)
	{
		return array(
			'id'         => (int) $board['id'],
			'title'      => $board['title'],
			'card_count' => (int) $board['card_count'],
		);
	}
}
