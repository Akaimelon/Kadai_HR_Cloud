<?php
/**
 * エラー画面を出すコントローラ。routes.php の _404_ から呼ばれる。
 *
 * Controller_Base を継承しないのは、未ログインでも404画面を出すため（ログイン画面へ転送しない）。
 */
class Controller_Error extends Controller
{
	/**
	 * 存在しないURLを開いたときの404画面。
	 *
	 * get_404 ではなく action_404 にするのは、POST など GET 以外で存在しないURLを開いたときにも呼ばれるようにするため。
	 * FuelPHP は「HTTPメソッド名_アクション名」が見つからないときだけ action_ を使う（core/classes/request.php）
	 *
	 * @return Response
	 */
	public function action_404()
	{
		return Response::forge(View::forge('404', array(
			'user_email' => Guard::user_email(),
		)), 404);
	}
}
