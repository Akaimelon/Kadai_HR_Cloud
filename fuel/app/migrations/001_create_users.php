<?php

namespace Fuel\Migrations;


class Create_users
{
	public function up()
	{
		\DBUtil::create_table('users', array(
			'id'            => array('type' => 'int', 'unsigned' => true, 'auto_increment' => true),
			'email'         => array('type' => 'varchar', 'constraint' => 255),
			'password_hash' => array('type' => 'varchar', 'constraint' => 255),
			'created_at'    => array('type' => 'datetime'),
		), array('id'), true, 'InnoDB');

		
		\DBUtil::create_index('users', 'email', 'users_email_unique', 'UNIQUE');
	}

	public function down()
	{
		\DBUtil::drop_table('users');
	}
}
