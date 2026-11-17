<?php
/**
 * API（JSON）用コントローラの基底クラス。
 *
 * before() でログインとCSRFトークンを確認する。確認の中身は画面用の Controller_Base と同じく
 * Guard にまとめている。APIはリダイレクトせず、ステータスコードとJSONで返す。
 * CSRFトークンは、JSが画面の<meta name="csrf-token">から読んで X-CSRF-Token ヘッダで送る。
 */
abstract class Controller_Api_Base extends Controller_Rest
{
	protected $format = 'json';

	public function before()
	{
		parent::before();

		switch (Guard::check(true, \Input::headers('X-CSRF-Token')))
		{
			case Guard::NOT_LOGGED_IN:
				throw new HttpJsonException(401, 'ログインしてください');

			case Guard::INVALID_TOKEN:
				throw new HttpJsonException(403, '画面を再読み込みしてから、もう一度操作してください');
		}
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
}
