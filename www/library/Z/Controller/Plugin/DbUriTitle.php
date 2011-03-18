<?php

class Z_Controller_Plugin_DbUriTitle extends Zend_Controller_Plugin_Abstract
{
	
	public function __construct()
	{
	}
	
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
    	if ($this->getRequest()->getModuleName()=='admin') return;
		$uri = $request->getRequestUri();
		
		if (!$titles = Z_Cache::getInstance()->load('z_titles'))
		{
			$table_titles = new Z_Model_Titles();
			$titles = $table_titles->fetchAll(NULL,'orderid asc');
			Z_Cache::getInstance()->save($titles,'z_titles');
		}

		foreach ($titles as $title) {
			if (strpos($uri,$title->uri)===0)
			{
				if ($title->title_block)
				{
					Z_Seo::addTitle($title->title);
				}
				else
				{
					Z_Seo::setTitle($title->title);
				}
			
				if ($title->description_block)
					Z_Seo::addDescription($title->description);
				else
				{
					Z_Seo::setDescription($title->description);
				}
				
				if ($title->keywords_block)
					Z_Seo::addKeywords($title->keywords);
				else
					Z_Seo::setKeywords($title->keywords);
			}
		}
		
    }
}
