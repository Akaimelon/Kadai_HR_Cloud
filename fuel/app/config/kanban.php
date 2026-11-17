<?php
/**
 * カンバン画面の見た目の設定。
 *
 * 選考ステージの名前と順番は statuses テーブルが持ち、ここは色だけを持つ（設計書0章 No.3）。
 * Tailwind はこのファイルからもクラス名を探すので、クラス名は省略せずに書く（tailwind/input.css の @source）。
 */
return array(
	'status_classes' => array(
		1 => 'bg-sky-100 text-sky-800',
		2 => 'bg-indigo-100 text-indigo-800',
		3 => 'bg-violet-100 text-violet-800',
		4 => 'bg-emerald-100 text-emerald-800',
		5 => 'bg-gray-200 text-gray-700',
	),

	// statuses に行を足したのに、ここに色を足し忘れたときの色
	'default_status_class' => 'bg-gray-100 text-gray-800',
);
