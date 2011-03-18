<?php

class Z_Admin_Controller_Cleancache extends Z_Admin_Controller_Action
{
	
	public function init()
	{
	}

	public function indexAction()
	{
		$this->disableRenderView();
		$cache = Z_Cache::getInstance();
		$cache->clean('all');
		Z_FlashMessenger::addMessage('Кэш очищен');
	}
	
}

