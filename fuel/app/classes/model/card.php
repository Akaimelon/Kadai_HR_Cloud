<?php
/**
 * 応募（cards テーブル）のモデル。1枚のカードが1人の応募者の1ポジションへの応募にあたる。
 *
 * カードを操作してよいかは、カードが入っているボードの Model_Board::can_access() で判定する。
 * カード側で別の条件を持たず、判定をボードの1か所にまとめるため。
 */
class Model_Card
{
	/**
	 * 応募者名とメモの最大文字数。APIの入力チェックと画面の maxlength・文字数の表示で同じ値を使うため、ここだけに書く。
	 * 応募者名は cards.title の varchar(100) に合わせる
	 */
	const TITLE_MAX_LENGTH = 100;
	const MEMO_MAX_LENGTH = 1000;

	/**
	 * ボードのカードを、列ごと・列の上から順に取得する
	 *
	 * @param  int $board_id
	 * @param  int $user_id
	 * @return array  各行は id・status_id・title・memo・sort_order・created_at・updated_at。操作できないボードなら空配列
	 */
	public static function find_by_board($board_id, $user_id)
	{
		if ( ! Model_Board::can_access($board_id, $user_id))
		{
			return array();
		}

		return \DB::select('id', 'status_id', 'title', 'memo', 'sort_order', 'created_at', 'updated_at')
			->from('cards')
			->where('board_id', '=', $board_id)
			->order_by('status_id')
			->order_by('sort_order')
			// sort_order が重なったときも、CardMover と同じ順（sort_order, id）に並べる
			->order_by('id')
			->execute()
			->as_array();
	}

	/**
	 * カードを1件取得する
	 *
	 * 存在しないカードも他人のカードも null を返し、呼び出し元が区別せずに404を返せるようにする。
	 *
	 * @param  int $card_id
	 * @param  int $user_id
	 * @return array|null  id・board_id・status_id・title・memo・sort_order・created_at・updated_at
	 */
	public static function find($card_id, $user_id)
	{
		$card = \DB::select('id', 'board_id', 'status_id', 'title', 'memo', 'sort_order', 'created_at', 'updated_at')
			->from('cards')
			->where('id', '=', $card_id)
			->execute()
			->current();

		if ( ! $card or ! Model_Board::can_access($card['board_id'], $user_id))
		{
			return null;
		}

		return $card;
	}

	/**
	 * カードを指定した列の一番下に作成する
	 *
	 * @param  int         $board_id
	 * @param  int         $user_id
	 * @param  int         $status_id  呼び出し元で Model_Status::exists() を確かめておく
	 * @param  string      $title
	 * @param  string|null $memo
	 * @return int|null  作成したカードのID。操作できないボード、またはロックを待つ間に削除されたボードなら null
	 */
	public static function create($board_id, $user_id, $status_id, $title, $memo)
	{
		if ( ! Model_Board::can_access($board_id, $user_id))
		{
			return null;
		}

		\DB::start_transaction();
		try
		{
			// 同じ列に同時に作成されると、両方が同じ MAX を読んで sort_order が重なるため
			if ( ! Model_Board::lock($board_id))
			{
				// そのまま INSERT すると外部キー違反で500になるため
				\DB::rollback_transaction(null, false);
				return null;
			}

			// 空の列では MAX が NULL になるので、-1 に置き換えて 0 から始める
			$next = \DB::select(\DB::expr('COALESCE(MAX(sort_order), -1) + 1 AS next'))
				->from('cards')
				->where('board_id', '=', $board_id)
				->where('status_id', '=', $status_id)
				->execute()
				->get('next');

			list($id) = \DB::insert('cards')
				->set(array(
					'board_id'   => $board_id,
					'status_id'  => $status_id,
					'title'      => $title,
					'memo'       => $memo,
					'sort_order' => $next,
					'created_at' => \DB::expr('NOW()'),
					'updated_at' => \DB::expr('NOW()'),
				))
				->execute();

			Model_Board::touch($board_id);

			\DB::commit_transaction();
		}
		// \Throwable で受けること、rollback_transaction(null, false) と、その失敗を内側の try で受ける理由は、
		// Kanban\CardMover::move() と同じ
		catch (\Throwable $e)
		{
			try
			{
				\DB::rollback_transaction(null, false);
			}
			catch (\Throwable $rollback_error)
			{
				\Log::error('ロールバックに失敗した: '.$rollback_error);
			}
			throw $e;
		}

		return (int) $id;
	}

	/**
	 * カードの応募者名とメモを変更する
	 *
	 * 呼び出し元が find() の確認を忘れても他人のカードを変えないよう、ここでも確認する。
	 *
	 * @param  int         $card_id
	 * @param  int         $user_id
	 * @param  string      $title
	 * @param  string|null $memo
	 * @return void
	 */
	public static function update($card_id, $user_id, $title, $memo)
	{
		$card = static::find($card_id, $user_id);
		if ( ! $card)
		{
			return;
		}

		static::write_and_touch($card['board_id'], function () use ($card_id, $title, $memo) {
			\DB::update('cards')
				->set(array(
					'title'      => $title,
					'memo'       => $memo,
					'updated_at' => \DB::expr('NOW()'),
				))
				->where('id', '=', $card_id)
				->execute();
		});
	}

	/**
	 * カードを削除する
	 *
	 * 列の sort_order に欠番ができるが、作成は MAX+1 なので困らない。
	 * 連番に振り直すのは移動のとき（Kanban\CardMover）に任せる。
	 *
	 * @param  int $card_id
	 * @param  int $user_id
	 * @return void
	 */
	public static function delete($card_id, $user_id)
	{
		$card = static::find($card_id, $user_id);
		if ( ! $card)
		{
			return;
		}

		static::write_and_touch($card['board_id'], function () use ($card_id) {
			\DB::delete('cards')
				->where('id', '=', $card_id)
				->execute();
		});
	}

	/**
	 * ボードをロックしてから $write でカードを書き、ボードの更新日時を変える。1つのトランザクションで行う
	 *
	 * touch() だけが失敗すると、カードは変わったのにボード一覧の並び（更新日時の新しい順）が古いまま残るため。
	 * 権限の確認は呼び出し元で済ませておく（確認と書き込みを分け、それぞれを単独で直せるようにする）。
	 *
	 * ロックを先に取るのは、Kanban\CardMover と同じ「ボード → カード」の順に行をロックするため。
	 * カードを先に書いてから touch() すると、移動と逆の順になり、互いに相手の行を待ってデッドロックになる
	 * （MySQL の2つの接続で 1213 が起きることを確かめた）。
	 *
	 * @param  int      $board_id
	 * @param  callable $write  カードを書く処理
	 * @return void
	 */
	protected static function write_and_touch($board_id, callable $write)
	{
		\DB::start_transaction();
		try
		{
			// 確認の後、ロックを待つ間にボードが削除されていたら、カードも CASCADE で消えているので何もしない
			if ( ! Model_Board::lock($board_id))
			{
				\DB::rollback_transaction(null, false);
				return;
			}

			$write();
			Model_Board::touch($board_id);

			\DB::commit_transaction();
		}
		// create() と同じ（理由は Kanban\CardMover::move()）
		catch (\Throwable $e)
		{
			try
			{
				\DB::rollback_transaction(null, false);
			}
			catch (\Throwable $rollback_error)
			{
				\Log::error('ロールバックに失敗した: '.$rollback_error);
			}
			throw $e;
		}
	}
}
