<?php

class Z_Seo
{
	protected static $title = array();
	protected static $description = array();
	protected static $keywords =array();
	protected static $order = NULL;
	protected static $separator = NULL;
	
	protected function __construct()
	{
		
	}
	
	public static function setTitle($title)
	{
		if (!is_string($title) && !$title) return ;
		self::$title = array($title);
	}

	public static function setDescription($desc)
	{
		if (!is_string($desc) && !$desc) return ;
		self::$description = array($desc);
	}
	
	public static function setKeywords($Keywords)
	{
		if (!is_string($Keywords) && !$Keywords) return ;
		self::$keywords = array($Keywords);
	}
	
	public static function addTitle($title)
	{
		if (!is_string($title) && !$title) return ;
		self::$title[] = $title;
	}

	public static function addDescription($desc)
	{
		if (!is_string($desc) && !$desc) return ;
		self::$description[] = $desc;
	}
	
	public static function addKeywords($Keywords)
	{
		if (!is_string($Keywords) && !$Keywords) return ;
		self::$keywords[] = $Keywords;
	}
	
	public static function getTitle()
	{
		$res_array = self::$title;
		if (self::getOrder() == 'prepend')
		{
			$res_array = array_reverse($res_array);
		}
		return implode(self::getSeparator(), $res_array);
	}

	public static function getDescription()
	{
		$res_array = self::$description;
		if (self::getOrder() == 'prepend')
		{
			$res_array = array_reverse($res_array);
		}
		return implode(', ', $res_array);
	}
	
	public static function getKeywords()
	{
		$res_array = self::$keywords;
		if (self::getOrder() == 'prepend')
		{
			$res_array = array_reverse($res_array);
		}
		return implode(', ', $res_array);
	}
	
	
	
	protected static function getOrder()
	{
		if (NULL === self::$order)
		{
			$default = 'prepend';
			$config = Zend_Registry::getInstance()->get('config')->site;
			if (!$config) self::$order = $default;
			else self::$order = $config->title->get('order',$default);
			unset($config);
		}
		return self::$order;
	}
	
	protected static function getSeparator()
	{
		if (NULL === self::$separator)
		{
			$default = ' — ';
			$config = Zend_Registry::getInstance()->get('config')->site;
			if (!$config) self::$sepsrator = $default;
			else self::$separator = $config->title->get('separator',$default);
			unset($config);
		}
		return self::$separator;
	}
	
}