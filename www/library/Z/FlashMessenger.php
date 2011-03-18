<?php

class Z_FlashMessenger {

	protected static $_messages = array();
	
	public static function addMessage($message)
	{
		self::$_messages[] = $message;
	}
	
	public static function getMessages()
	{
		return self::$_messages;
	}

	public static function getMessagesHtml($flash_html_begin,$flash_html_end)
	{
		$flash_html = $flash_html_begin.implode($flash_html_end.$flash_html_begin,self::$_messages).$flash_html_end;
		return $flash_html;
	}
	
}

?>