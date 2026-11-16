<?php
class Controller_Boards extends Controller_Base
{
	public function get_index()
	{
		$this->template->title = 'ボード一覧';
		$this->template->content = \View::forge('boards/index');
	}
}
