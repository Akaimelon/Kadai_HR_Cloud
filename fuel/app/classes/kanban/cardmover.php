<?php
/**
 * カードを別の位置へ移動し、列の並び順（sort_order）を振り直すクラス。
 *
 * 並び替えはカードの作成・編集とは別の手順（複数のカードを1つのトランザクションで更新する）なので、
 * Model_Card から切り出し、Kanban 名前空間にまとめた（課題条件 No.5）。
 */
namespace Kanban;

class CardMover
{
	/**
	 * カードを指定した列の、上から $sort_order 番目に移動する
	 *
	 * 移動元と移動先の列を、0からの連番に振り直す。削除でできた欠番もここで詰まる。
	 *
	 * @param  array $card        \Model_Card::find() で取得したカード。呼び出し元で、操作してよいカードか確かめておく
	 * @param  int   $status_id   移動先の列。呼び出し元で \Model_Status::exists() を確かめておく
	 * @param  int   $sort_order  移動先の列での挿入位置（0始まり）。範囲外なら末尾に丸める
	 * @return void
	 */
	public static function move(array $card, $status_id, $sort_order)
	{
		$card_id = (int) $card['id'];
		$board_id = (int) $card['board_id'];
		$from_status_id = (int) $card['status_id'];
		$status_id = (int) $status_id;
		$now = date('Y-m-d H:i:s');

		\DB::start_transaction();
		try
		{
			// 同じボードで同時に移動されると、両方が古い並び順を読んで連番が崩れる。
			// ボードの行をロックして、同じボードの移動を1つずつ順番に処理させる
			\DB::query('SELECT id FROM boards WHERE id = :id FOR UPDATE')
				->param('id', $board_id)
				->execute();

			$ids = static::column_card_ids($board_id, $status_id, $card_id);
			// 別のタブで開いた画面からだと、枚数がずれて範囲外の位置が届くことがあるため
			$position = max(0, min((int) $sort_order, count($ids)));
			array_splice($ids, $position, 0, array($card_id));
			static::renumber($ids);

			if ($from_status_id !== $status_id)
			{
				static::renumber(static::column_card_ids($board_id, $from_status_id, $card_id));
			}

			// updated_at は「最後に選考ステージや内容が変わった日時」なので、動かしたカードにだけ入れる（設計書4章）
			\DB::update('cards')
				->set(array(
					'status_id'  => $status_id,
					'updated_at' => $now,
				))
				->where('id', '=', $card_id)
				->execute();

			\Model_Board::touch($board_id);

			\DB::commit_transaction();
		}
		// \Exception だけだと、PHP 7 の \Error（TypeError など）が起きたときにロールバックされず、
		// トランザクションが開いたまま残るため、両方の親の \Throwable で受ける
		catch (\Throwable $e)
		{
			// false にすると、自分が始めた段だけを戻す。テストのように外側でトランザクションを始めていても、
			// 外側まで巻き込んで戻さないため
			\DB::rollback_transaction(false);
			throw $e;
		}
	}

	/**
	 * 列のカードIDを、上から順に取得する（移動するカードを除く）
	 *
	 * @param  int $board_id
	 * @param  int $status_id
	 * @param  int $exclude_card_id
	 * @return int[]
	 */
	protected static function column_card_ids($board_id, $status_id, $exclude_card_id)
	{
		$rows = \DB::select('id')
			->from('cards')
			->where('board_id', '=', $board_id)
			->where('status_id', '=', $status_id)
			->where('id', '!=', $exclude_card_id)
			// sort_order が重なっていても、毎回同じ順に並ぶように id でも並べる
			->order_by('sort_order')
			->order_by('id')
			->execute()
			->as_array();

		return array_map('intval', array_column($rows, 'id'));
	}

	/**
	 * 並べたIDの順に、sort_order を0からの連番にする
	 *
	 * @param  int[] $ids
	 * @return void
	 */
	protected static function renumber(array $ids)
	{
		foreach ($ids as $order => $id)
		{
			\DB::update('cards')
				->value('sort_order', $order)
				->where('id', '=', $id)
				->execute();
		}
	}
}
