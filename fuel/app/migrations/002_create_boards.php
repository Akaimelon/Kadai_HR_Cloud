<?php

namespace Fuel\Migrations;

class Create_boards
{
	public function up()
	{
		\DBUtil::create_table('boards', array(
			'id'         => array('type' => 'int', 'unsigned' => true, 'auto_increment' => true),
			'owner_id'   => array('type' => 'int', 'unsigned' => true),
			'title'      => array('type' => 'varchar', 'constraint' => 100),
			'updated_at' => array('type' => 'datetime'),
		), array('id'), true, 'InnoDB', null, array(
			array(
				'constraint' => 'boards_owner_id_fk',
				'key'        => 'owner_id',
				'reference'  => array('table' => 'users', 'column' => 'id'),
				// ボードはユーザーの所有物なので、ユーザー削除時に一緒に消す設定のため
				'on_delete'  => 'CASCADE',
			),
		));
	}

	public function down()
	{
		\DBUtil::drop_table('boards');
	}
}
