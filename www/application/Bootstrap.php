<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

	protected function _initRegistry()
	{
		$this->bootstrap('session');
		
		//конфиг в реестр
		$config = new Zend_Config($this->getApplication()->getOptions(),true);
		Zend_Registry::set('config',$config);
		
		//берем из сессии прошлый uri и кладем в конфиг
		if (isset($_SERVER['HTTP_HOST']))
		{
			$lastUriNamespace = new Zend_Session_Namespace('last_page');
			$uri = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			if ($lasturi = $lastUriNamespace->lastUri);
			else $lasturi = $uri;
			$config->lastUri = $lasturi;
			$lastUriNamespace->lastUri = $uri;
		}
	}

	protected function _initAutoload()
	{
		$autoloader = new Zend_Application_Module_Autoloader(array(
			'namespace' => 'Site',
			'basePath'  => APPLICATION_PATH,
		));
		return $autoloader;
	}

	protected function _initLocale()
	{
		setlocale(LC_ALL, "ru_RU.UTF-8");
	}
	
}
