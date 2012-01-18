<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Лицензия
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

class Z_Cache {
	
	protected static $_instance = NULL;
	protected static $_backend=NULL;
	
	
	protected function __construct()
	{
		$config = Zend_Registry::get('config')->site;
		if (extension_loaded('memcache'))
		{
			$oBackend = new Zend_Cache_Backend_Memcached(
				array(
					'servers' => array( array(
					'host' => '127.0.0.1',
					'port' => '11211'
				)),
				'compression' => false
			));
		}
		else
		{
			$oBackend = new Zend_Cache_Backend_File(
				array(
					'cache_dir'		=>	APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR,
			));
		}
		self::$_backend = $oBackend;

		$oFrontend = new Zend_Cache_Core(array(
			'caching'			=> $config->get('cache_on',false),
			'lifetime'			=> $config->get('cache_life_time',60),	
			'cache_id_prefix'	=> trim(str_replace(array(DIRECTORY_SEPARATOR,'.','-',':','/','\\'),'_',SITE_PATH),'_').'_',
//			'logging'			=> false,
//			'logger'			=> Z_Log::getInstance(),
			'write_control'		=> true,
			'automatic_serialization' => true,
			'ignore_user_abort'	=> true
    	));
		
    	$oCache = Zend_Cache::factory( $oFrontend, $oBackend );
    	
		self::$_instance = $oCache;
	}
	
	/**
	 * @return Zend_Cache_Core
	 */
	public static function getInstance()
	{
		if (NULL === self::$_instance)
		{
			$cacher = new self();
		}
		return self::$_instance;
	}
	
	public static function getbackend()
	{
		self::getInstance();
		return self::$_backend;
	}
	
}

?>