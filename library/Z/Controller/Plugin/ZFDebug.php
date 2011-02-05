<?php

class Z_Controller_Plugin_ZFDebug extends Zend_Controller_Plugin_Abstract
{

	public function __construct()
	{
	}

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
    	if ($request->getModuleName()=='admin' || APPLICATION_ENV != 'development') return;

		$frontController = Zend_Controller_Front::getInstance();
		$options = array(
		'plugins' => array(
			'Variables',
			'Database' => array('adapter' => Z_Db_Table::getDefaultAdapter()),
			'File' => array('basePath' => SITE_PATH),
			'Memory',
			'Time',
			'Registry',
			'Cache' => array('backend' => Z_Cache::getbackend()),
			'Exception'
		));
		$debug = new ZFDebug_Controller_Plugin_Debug($options);
		$frontController->registerPlugin($debug);
		
    }
        
}
