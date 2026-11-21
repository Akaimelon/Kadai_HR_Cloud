<?php
class Controller_Boards extends Controller_Base
{
	public function get_index()
	{
		$this->template->title = 'ボード一覧';
		// JSで件数の色分けに使うので、JSONにして渡す（ビューに渡すときにHTML用にエスケープされる）
		$this->template->content = \View::forge('boards/index', array(
			'statuses_json' => json_encode($this->statuses(), JSON_UNESCAPED_UNICODE),
		));

		return $this->template;
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

		$statuses = $this->statuses();
		$this->template->title = $board['title'];
		$this->template->content = \View::forge('boards/view', array(
			'board'         => array(
				'id'         => (int) $board['id'],
				'title'      => $board['title'],
				'updated_at' => $board['updated_at'],
			),
			// 列はPHPで出し、カードのモーダルに出す選考ステージ名と色はJSで使うため、両方渡す
			'statuses'      => $statuses,
			'statuses_json' => json_encode($statuses, JSON_UNESCAPED_UNICODE),
		));

		return $this->template;
	}

	/**
	 * 選考ステージに、画面で使う色のクラスを付けて返す
	 *
	 * @return array  各行は id・title・dot・chip・heading
	 */
	protected function statuses()
	{
		\Config::load('kanban', true);
		$statuses = array();
		foreach (Model_Status::find_all() as $status)
		{
			$style = \Config::get('kanban.status_styles.'.$status['id'], \Config::get('kanban.default_status_style'));
			$statuses[] = array(
				'id'    => (int) $status['id'],
				'title' => $status['title'],
			) + $style;
		}

		return $statuses;
	}
}
