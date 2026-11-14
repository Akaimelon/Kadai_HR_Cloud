<?php
/**
 * The test database settings. These get merged with the global settings.
 *
 * This environment is primarily used by unit tests, to run on a controlled environment.
 */

return array(
	'default' => array(
		'type'        => 'mysqli',
		'connection'  => array(
			'hostname'   => 'db',
			'database'   => 'fuelphp_test',
			'username'   => 'root',
			'password'   => 'root',
			'persistent' => false,
		),
		'identifier'   => '`',
		'table_prefix' => '',
		'charset'      => 'utf8mb4',
		'collation'    => 'utf8mb4_unicode_ci',
		'enable_cache' => true,
		'profiling'    => false,
	),
);
