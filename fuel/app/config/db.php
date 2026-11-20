<?php
/**
 * Use this file to override global defaults.
 *
 * See the individual environment DB configs for specific config information.
 */

return array(
	'default' => array(
		'charset'   => 'utf8mb4',
		'collation' => 'utf8mb4_unicode_ci',
		// 日時を DB の NOW() で決めるため、MySQL を PHP（php.ini の Asia/Tokyo）と同じ日本時間にそろえる。
		// 'Asia/Tokyo' と名前で書くと、タイムゾーンの表を入れていない MySQL では使えない。日本は夏時間がないので時差で書く
		'time_zone' => '+09:00',
	),
);
