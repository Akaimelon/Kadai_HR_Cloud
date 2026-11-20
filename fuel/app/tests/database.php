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

	public function test_DBのタイムゾーンは日本時間()
	{
		$result = DB::select(DB::expr('@@session.time_zone AS tz'), DB::expr('NOW() AS now'))->execute()->current();

		$this->assertSame('+09:00', $result['tz']);
		$this->assertLessThanOrEqual(5, abs(strtotime($result['now']) - time()));
	}
}
