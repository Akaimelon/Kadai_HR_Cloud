<?php
/**
 * before() の中でAPIの処理を止め、エラーをJSONで返すための例外。
 *
 * before() の戻り値はFuelPHPに無視されるため、処理を止めるには例外を投げる。
 * HttpException を継承した例外は、FuelPHPのエラーハンドラが response() の内容をそのまま返す。
 */
class HttpJsonException extends \HttpException
{
	/**
	 * @var int
	 */
	protected $status;

	/**
	 * @param int    $status  HTTPステータスコード
	 * @param string $message JSONの error に入れるメッセージ
	 */
	public function __construct($status, $message)
	{
		parent::__construct($message);
		$this->status = $status;
	}

	public function response()
	{
		return new \Response(
			json_encode(array('error' => $this->getMessage()), JSON_UNESCAPED_UNICODE),
			$this->status,
			array('Content-Type' => 'application/json')
		);
	}
}
