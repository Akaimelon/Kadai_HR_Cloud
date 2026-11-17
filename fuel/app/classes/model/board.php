<?php
/**
 * ボード（boards テーブル）のモデル。1つのボードが1つの募集ポジションにあたる。
 *
 * ボードを操作してよいかの判定は can_access() だけで行う。
 * 共有・権限管理を足すときに、変更をこのメソッドの中だけで済ませるため（設計書4章）。
 */
class Model_Board
{
	/**
	 * ボード名の最大文字数。boards.title の varchar(100) に合わせる。
	 * APIの入力チェックと画面の maxlength で同じ値を使うため、ここだけに書く
	 */
	const TITLE_MAX_LENGTH = 100;

	/**
	 * ユーザーのボード一覧を、更新日時の新しい順に取得する
	 *
	 * 削除の確認ダイアログで「応募N件も削除されます」と出すため、応募（カード）の件数も返す。
	 *
	 * @param  int $user_id
	 * @return array  各行は id・title・updated_at・card_count
	 */
	public static function find_by_owner($user_id)
	{
		return \DB::select(
				'boards.id',
				'boards.title',
				'boards.updated_at',
				\DB::expr('COUNT(cards.id) AS card_count')
			)
			->from('boards')
			->join('cards', 'LEFT')->on('cards.board_id', '=', 'boards.id')
			->where('boards.owner_id', '=', $user_id)
			->group_by('boards.id')
			->order_by('boards.updated_at', 'desc')
			// updated_at は秒までなので、同じ秒に作ったボードは後から作った方を上にするため
			->order_by('boards.id', 'desc')
			->execute()
			->as_array();
	}

	/**
	 * ユーザーのボードごとに、選考ステージ別の応募の件数を数える
	 *
	 * ボード一覧で、ボードを開かずにどの段階に何人いるかを見せるため。
	 * 件数が0のステージは行がないので、呼び出し側で0として扱う。
	 *
	 * @param  int $user_id
	 * @return array  ボードIDをキーに、選考ステージIDをキーにした件数の配列
	 */
	public static function count_cards_by_status($user_id)
	{
		$rows = \DB::select('cards.board_id', 'cards.status_id', \DB::expr('COUNT(cards.id) AS card_count'))
			->from('cards')
			->join('boards')->on('boards.id', '=', 'cards.board_id')
			->where('boards.owner_id', '=', $user_id)
			->group_by('cards.board_id', 'cards.status_id')
			->execute()
			->as_array();

		$counts = array();
		foreach ($rows as $row)
		{
			$counts[(int) $row['board_id']][(int) $row['status_id']] = (int) $row['card_count'];
		}

		return $counts;
	}

	/**
	 * ボードを作成する
	 *
	 * @param  int    $owner_id
	 * @param  string $title
	 * @return int  作成したボードのID
	 */
	public static function create($owner_id, $title)
	{
		list($id) = \DB::insert('boards')
			->set(array(
				'owner_id'   => $owner_id,
				'title'      => $title,
				'updated_at' => date('Y-m-d H:i:s'),
			))
			->execute();

		return (int) $id;
	}

	/**
	 * ボードを1件取得する
	 *
	 * 存在しないボードと他人のボードは、どちらも null を返す。
	 * 呼び出し元が区別せずに404を返せるようにするため（can_access() と同じ）。
	 *
	 * @param  int $board_id
	 * @param  int $user_id
	 * @return array|null  id・title・updated_at
	 */
	public static function find($board_id, $user_id)
	{
		if ( ! static::can_access($board_id, $user_id))
		{
			return null;
		}

		return \DB::select('id', 'title', 'updated_at')
			->from('boards')
			->where('id', '=', $board_id)
			->execute()
			->current();
	}

	/**
	 * そのユーザーがボードを操作してよいか
	 *
	 * 今は所有者だけが操作できる。存在しないボードも false を返し、
	 * 呼び出し元が他人のボードと区別せずに404を返せるようにする。
	 *
	 * @param  int $board_id
	 * @param  int $user_id
	 * @return bool
	 */
	public static function can_access($board_id, $user_id)
	{
		$row = \DB::select('id')
			->from('boards')
			->where('id', '=', $board_id)
			->where('owner_id', '=', $user_id)
			->execute()
			->current();

		return (bool) $row;
	}

	/**
	 * ボード名を変更する
	 *
	 * 呼び出し元が can_access() の確認を忘れても他人のボードを変えないよう、ここでも確認する。
	 * owner_id を where に直接書かないのは、共有を足すときに can_access() だけを変えれば済むようにするため。
	 *
	 * @param  int    $board_id
	 * @param  int    $user_id
	 * @param  string $title
	 * @return void
	 */
	public static function rename($board_id, $user_id, $title)
	{
		if ( ! static::can_access($board_id, $user_id))
		{
			return;
		}

		\DB::update('boards')
			->set(array(
				'title'      => $title,
				'updated_at' => date('Y-m-d H:i:s'),
			))
			->where('id', '=', $board_id)
			->execute();
	}

	/**
	 * ボードの更新日時を今にする
	 *
	 * カードを操作したボードを一覧の上に出すため（設計書4章）。
	 * 権限の確認は、カードを操作する呼び出し元で済ませている前提にする。
	 *
	 * @param  int $board_id
	 * @return void
	 */
	public static function touch($board_id)
	{
		\DB::update('boards')
			->value('updated_at', date('Y-m-d H:i:s'))
			->where('id', '=', $board_id)
			->execute();
	}

	/**
	 * ボードを削除する。カードは外部キーの ON DELETE CASCADE で一緒に消える
	 *
	 * rename() と同じ理由で、ここでも can_access() を確認する。
	 *
	 * @param  int $board_id
	 * @param  int $user_id
	 * @return void
	 */
	public static function delete($board_id, $user_id)
	{
		if ( ! static::can_access($board_id, $user_id))
		{
			return;
		}

		\DB::delete('boards')
			->where('id', '=', $board_id)
			->execute();
	}
}
