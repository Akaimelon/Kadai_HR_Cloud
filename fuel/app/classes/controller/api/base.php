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
	 * 画面は文字列か数値しか送らない。配列などを (string) にすると Notice、本文がオブジェクトでないと
	 * Input::json() の中で Warning になり、どちらも500になるので、Validation に渡す前に422にする
	 *
	 * @param  array $keys  項目名
	 * @return array  項目名 => 文字列（送られていない項目と null は空文字）
	 * @throws HttpJsonException  画面が送らない形のとき（422）
	 */
	protected function json_strings(array $keys)
	{
		$message = '送られた値が正しくありません。画面を再読み込みしてください';

		if (is_scalar(json_decode(\Input::raw())))
		{
			throw new HttpJsonException(422, $message);
		}

		$json = \Input::json();
		$values = array();
		foreach ($keys as $key)
		{
			$value = isset($json[$key]) ? $json[$key] : null;
			// true が "1" になって保存されないよう、真偽値も通さない
			if ( ! ($value === null or is_string($value) or is_int($value) or is_float($value)))
			{
				throw new HttpJsonException(422, $message);
			}
			$values[$key] = (string) $value;
		}

		return $values;
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
