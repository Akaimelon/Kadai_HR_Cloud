<?php
/**
 * 選考ステージの色の設定。
 *
 * 選考ステージの名前と順番は statuses テーブルが持ち、ここは色だけを持つ（設計書0章 No.3）。
 * Tailwind はこのファイルからもクラス名を探すので、クラス名は省略せずに書く（tailwind/input.css の @source）。
 *
 * dot：列の見出しやボード一覧の件数の前に付ける点と、ボード一覧の積み上げバー
 * chip：カードのモーダルで、選考ステージ名を囲む札
 * heading：カンバンの列の見出しの文字
 */
return array(
	'status_styles' => array(
		1 => array('dot' => 'bg-stage-screening', 'chip' => 'bg-chip-neutral text-ink', 'heading' => 'text-ink'),
		2 => array('dot' => 'bg-stage-first', 'chip' => 'bg-chip-blue text-primary-dark', 'heading' => 'text-ink'),
		3 => array('dot' => 'bg-stage-final', 'chip' => 'bg-primary-tint text-primary-dark', 'heading' => 'text-ink'),
		// 内定はゴールの列なので、名前の文字にも色を付けて目立たせる
		4 => array('dot' => 'bg-stage-offer', 'chip' => 'bg-chip-amber text-stage-offer-ink', 'heading' => 'text-stage-offer-ink'),
		// 不採用・辞退は選考が終わった列なので、目立たせない
		5 => array('dot' => 'bg-stage-closed', 'chip' => 'bg-chip-neutral text-ink-soft', 'heading' => 'text-ink'),
	),

	// statuses に行を足したのに、ここに色を足し忘れたときの色
	'default_status_style' => array('dot' => 'bg-faint', 'chip' => 'bg-chip-neutral text-ink', 'heading' => 'text-ink'),
);
