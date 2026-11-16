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
		$title = $this->validated_title();
		$id = Model_Board::create(Guard::user_id(), $title);

		return $this->response(array(
			'board' => $this->to_json(array('id' => $id, 'title' => $title, 'card_count' => 0)),
		), 201);
	}

	/**
	 * @param string $id
	 */
	public function put_item($id)
	{
		$this->ensure_accessible($id);
		$title = $this->validated_title();
		Model_Board::rename($id, Guard::user_id(), $title);

		return $this->response(array(
			'board' => array('id' => (int) $id, 'title' => $title),
		));
	}

	/**
	 * @param string $id
	 */
	public function delete_item($id)
	{
		$this->ensure_accessible($id);
		Model_Board::delete($id, Guard::user_id());

		return $this->response(array(), 204);
	}

	/**
	 * 他人のボードにも404を返すのは、そのIDのボードがあること自体を知らせないため（設計書5章）
	 *
	 * @param  string $id
	 * @throws HttpJsonException
	 */
	protected function ensure_accessible($id)
	{
		if ( ! Model_Board::can_access($id, Guard::user_id()))
		{
			throw new HttpJsonException(404, 'ボードが見つかりません。画面を再読み込みしてください');
		}
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
