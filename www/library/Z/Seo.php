<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации
 * (в дальнейшем именуемыми "Программное Обеспечение"),безвозмездно использовать Программное Обеспечение без ограничений,
 * включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам,
 * которым предоставляется данное Программное Обеспечение, соблюдении следующих условий:
 *
 * Вышеупомянутый копирайт и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
 *
 * При копировании, добавлении, изменении, распространении, продаже, публикации и сублицензировании программного обеспечения,
 * авторство может быть только дополнено, но не удалено или изменено на другое.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ,
 * ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И
 * ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА,
 * УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ
 * ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 *
 */

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
		if (!is_string($title) || !$title) return ;
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