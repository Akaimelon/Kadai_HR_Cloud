<?php
/**
 * Validation の既定のメッセージ（日本語）。
 *
 * 既定は英語なので、どの画面・APIでも同じ文になるものをここにまとめる。
 * ここにないルールは fuel/core/lang/en/validation.php の英語になる。
 * 画面ごとに文を変えたいもの（「画面を再読み込みしてください」など）は、コントローラで set_message() する。
 */
return array(
	'required'    => ':labelを入力してください',
	'min_length'  => ':labelは:param:1文字以上で入力してください',
	'max_length'  => ':labelは:param:1文字以内で入力してください',
	'valid_email' => ':labelの形式が正しくありません',
);
