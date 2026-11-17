<?php
class Controller_Boards extends Controller_Base
{
	public function get_index()
	{
		$this->template->title = 'ボード一覧';
		$this->template->content = \View::forge('boards/index');
	}

	/**
	 * @param string $id
	 */
	public function get_view($id)
	{
		$board = Model_Board::find($id, Guard::user_id());

		// 他人のボードも404にするのは、そのIDのボードがあること自体を知らせないため（設計書5章）
		if ( ! $board)
		{
			throw new \HttpNotFoundException();
		}

		\Config::load('kanban', true);
		$columns = array();
		foreach (Model_Status::find_all() as $status)
		{
			$columns[] = array(
				'id'    => (int) $status['id'],
				'title' => $status['title'],
				'class' => \Config::get('kanban.status_classes.'.$status['id'], \Config::get('kanban.default_status_class')),
			);
		}

		$this->template->title = $board['title'];
		$this->template->content = \View::forge('boards/view', array(
			'board_id' => (int) $board['id'],
			'columns'  => $columns,
		));
	}
}
