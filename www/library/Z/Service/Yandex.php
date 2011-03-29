<?php


class Z_Service_Yandex
{
	protected $uri;
	
	public function __construct()
	{
	}

	public static function getCY ($_url)
	{
		$_url = preg_replace('/^http:\/\//', '', $_url);
		$_uri = "http://bar-navig.yandex.ru/u?ver=2&show=32&url=http://" . urlencode($_url) . "&show=1";
		$fd = @fopen($_uri, "r"); // считываем файл
		if ($fd)
		{
			$haystack = '';
			while ($buffer = fgets($fd, 4096))
				$haystack .= $buffer;
			fclose($fd);
			// выискиваем параметр тИЦ
			preg_match("/<tcy rang=\"(.*)\" value=\"(.*)\"\/>/isU", $haystack, $cy);
			// возвращаем полученное значение
			return (int) $cy[2];
		}
		else
			return 0;
	} 
	
}