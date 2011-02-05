<?php

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