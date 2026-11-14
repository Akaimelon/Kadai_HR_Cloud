<?php

namespace Fuel\Migrations;

class Create_statuses
{
	public function up()
	{
		\DBUtil::create_table('statuses', array(
			'id'       => array('type' => 'tinyint', 'unsigned' => true),
			'title'    => array('type' => 'varchar', 'constraint' => 20),
			'sequence' => array('type' => 'tinyint', 'unsigned' => true),
		), array('id'), true, 'InnoDB');

		\DB::insert('statuses')
			->columns(array('id', 'title', 'sequence'))
			->values(array(1, '書類選考', 1))
			->values(array(2, '一次面接', 2))
			->values(array(3, '最終面接', 3))
			->values(array(4, '内定', 4))
			->values(array(5, '不採用・辞退', 5))
			->execute();
	}

	public function down()
	{
		\DBUtil::drop_table('statuses');
	}
}
