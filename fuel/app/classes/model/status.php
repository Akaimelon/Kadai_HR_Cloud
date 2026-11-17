<?php
/**
 * 選考ステージ（statuses テーブル）のモデル。カンバンの列にあたる。
 *
 * 画面から追加・削除しないマスタなので、読み取りだけを持つ。
 */
class Model_Status
{
	/**
	 * 選考ステージを、カンバンの左の列から順に取得する
	 *
	 * @return array  各行は id・title
	 */
	public static function find_all()
	{
		return \DB::select('id', 'title')
			->from('statuses')
			->order_by('sequence', 'asc')
			->execute()
			->as_array();
	}

	/**
	 * その選考ステージがあるか
	 *
	 * 外部キーでも存在しない ID は拒否されるが、DBのエラーではなく422で返すため、先に確かめる。
	 *
	 * @param  int $status_id
	 * @return bool
	 */
	public static function exists($status_id)
	{
		$row = \DB::select('id')
			->from('statuses')
			->where('id', '=', $status_id)
			->execute()
			->current();

		return (bool) $row;
	}
}
