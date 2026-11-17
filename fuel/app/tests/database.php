<?php

/**
 * テストの環境が動くことを確かめるサンプル。
 * php oil test で実行すると、FUEL_ENV=test になり、config/test/db.php のテスト用DBに接続する。
 *
 * @group App
 */
class Test_Database extends TestCase
{
	public function test_テスト用DBに接続している()
	{
		$result = DB::select(DB::expr('DATABASE() AS name'))->execute()->current();

		$this->assertSame('fuelphp_test', $result['name']);
	}
}
