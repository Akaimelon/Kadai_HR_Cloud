<?php
/**
 * FuelPHPのCookieクラスを拡張し、SameSite属性を付けられるようにする
 *
 * FuelPHP 1.8 の Cookie::set() は setcookie() にSameSiteを渡さない
 * PHP 7.3 から setcookie() が配列でオプションを受け取れるようになったので、
 * set() だけを上書きして config.php の cookie.samesite を渡す
 * bootstrap.php の Autoloader::add_classes() で、コアの Cookie の代わりにこのクラスを使う
 */
class Cookie extends \Fuel\Core\Cookie
{
	public static function set($name, $value, $expiration = null, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		// CLI（oilなど）ではCookieを送れない
		if (\Fuel::$is_cli)
		{
			return false;
		}

		$value = \Fuel::value($value);

		// 引数がなければ config.php の cookie の値を使う（ここまではコアと同じ）
		is_null($expiration) and $expiration = static::$config['expiration'];
		is_null($path) and $path = static::$config['path'];
		is_null($domain) and $domain = static::$config['domain'];
		is_null($secure) and $secure = static::$config['secure'];
		is_null($http_only) and $http_only = static::$config['http_only'];

		$expiration = $expiration > 0 ? $expiration + time() : 0;

		$options = array(
			'expires'  => $expiration,
			'path'     => $path,
			'domain'   => (string) $domain,
			'secure'   => (bool) $secure,
			'httponly' => (bool) $http_only,
		);

		if ( ! empty(static::$config['samesite']))
		{
			$options['samesite'] = static::$config['samesite'];
		}

		return setcookie($name, $value, $options);
	}
}
