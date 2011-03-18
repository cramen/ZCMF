<?php

class Z_Cache_Output {
	
	protected static $_instance = NULL;
	
	
	protected function __construct()
	{
		$config = Zend_Registry::get('config')->site;
		$oBackend = Z_Cache::getbackend();

		$oFrontend = new Zend_Cache_Frontend_Output(array(
			'caching'			=> $config->get('cache_on',false),
			'lifetime'			=> $config->get('cache_life_time',60),	
			'cache_id_prefix'	=> trim(str_replace(DIRECTORY_SEPARATOR,'_',SITE_PATH),'_').'_',
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
	 * @return Zend_Cache_Frontend_Output
	 */
	public static function getInstance()
	{
		if (NULL === self::$_instance)
		{
			$cacher = new self();
		}
		return self::$_instance;
	}
}

?>