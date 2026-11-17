<?php

/**
 * Model_Status のテスト。statuses はマイグレーションの初期データ（1〜5）を使う。
 *
 * @group App
 */
class Test_Model_Status extends TestCase
{
	public function test_初期データの選考ステージはある()
	{
		$this->assertTrue(Model_Status::exists(1));
		$this->assertTrue(Model_Status::exists(5));
	}

	public function test_存在しない選考ステージはない()
	{
		$this->assertFalse(Model_Status::exists(0));
		$this->assertFalse(Model_Status::exists(6));
	}
}
