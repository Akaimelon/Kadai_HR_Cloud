<?php
/**
 * FuelPHPのMySQLi接続クラスを拡張し、接続ごとにタイムゾーンを設定する
 *
 * 日時は DB の NOW() で決めるので、MySQL のタイムゾーンを日本時間にそろえる必要がある。
 * サーバーの設定（my.cnf）は Docker の db コンテナにしか効かず、本番・ステージングで別の MySQL につなぐと変わってしまう。
 * FuelPHP 1.8 の DB 設定にはタイムゾーンの項目がないため、接続した直後に SET time_zone を実行する
 * bootstrap.php の Autoloader::add_classes() で、コアの Database_MySQLi_Connection の代わりにこのクラスを使う
 */
class Database_MySQLi_Connection extends \Fuel\Core\Database_MySQLi_Connection
{
	public function connect()
	{
		if ($this->_connection)
		{
			return;
		}

		parent::connect();

		if (empty($this->_config['time_zone']))
		{
			return;
		}

		if ($this->_connection->query('SET time_zone = '.$this->escape($this->_config['time_zone'])) === false)
		{
			throw new \Database_Exception($this->_connection->error, $this->_connection->errno);
		}
	}
}
