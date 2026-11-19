<?php
/**
 * FuelPHPのInputクラスを拡張し、リクエストの本文をそのまま読めるようにする
 *
 * 本文が "abc" のようなオブジェクトでないJSONだと、コアの Input::json() は中の foreach で Warning を出して500になる。
 * 呼ぶ前に本文を確かめたいが、FuelPHP 1.8 の Input には本文をそのまま返すメソッドがない。
 * php://input を自分で読まず Input が覚えた本文を使うのは、Input::json() と同じものを確かめるため
 * bootstrap.php の Autoloader::add_classes() で、コアの Input の代わりにこのクラスを使う
 */
class Input extends \Fuel\Core\Input
{
	/**
	 * @return string
	 */
	public static function raw()
	{
		static::$php_input === null and static::$php_input = file_get_contents('php://input');

		return static::$php_input;
	}
}
