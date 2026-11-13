<?php

namespace Fuel\Migrations;

class Create_cards
{
	public function up()
	{
		\DBUtil::create_table('cards', array(
			'id'         => array('type' => 'int', 'unsigned' => true, 'auto_increment' => true),
			'board_id'   => array('type' => 'int', 'unsigned' => true),
			'status_id'  => array('type' => 'tinyint', 'unsigned' => true, 'default' => 1),
			'title'      => array('type' => 'varchar', 'constraint' => 100),
			'memo'       => array('type' => 'text', 'null' => true),
			'sort_order' => array('type' => 'int', 'unsigned' => true, 'default' => 0),
			'created_at' => array('type' => 'datetime'),
			// 他のカードの sort_order 更新で変わらないよう、ON UPDATE CURRENT_TIMESTAMP は付けない
			'updated_at' => array('type' => 'datetime'),
		), array('id'), true, 'InnoDB');

		// 列ごとの並び順取得を速くする。board_id の外部キー用インデックスも兼ねるので、外部キーより先に作る
		\DBUtil::create_index('cards', array('board_id', 'status_id', 'sort_order'), 'cards_board_status_sort_index');

		// カードはボードの中身なので、一緒に消すため
		\DBUtil::add_foreign_key('cards', array(
			'constraint' => 'cards_board_id_fk',
			'key'        => 'board_id',
			'reference'  => array('table' => 'boards', 'column' => 'id'),
			'on_delete'  => 'CASCADE',
		));

		// カードが残っているのに、選考ステージを誤って消してしまわないようにするため
		\DBUtil::add_foreign_key('cards', array(
			'constraint' => 'cards_status_id_fk',
			'key'        => 'status_id',
			'reference'  => array('table' => 'statuses', 'column' => 'id'),
			'on_delete'  => 'RESTRICT',
		));
	}

	public function down()
	{
		\DBUtil::drop_table('cards');
	}
}
