<?php

class Z_Controller_Plugin_Structure extends Zend_Controller_Plugin_Abstract
{

	/**
	 * 
	 * @var Z_Model_Structure
	 */
	protected $_model = NULL;
	
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
    	if ($this->getRequest()->getParam('module')=='admin') return;
    	
    	$this->_model = new Z_Model_Structure();
    	
    	$items = $this->getMenuArray();
		$navigation = new Zend_Navigation($items);
		Zend_Registry::set('navigation',$navigation);
    }
    
    private function getMenuArray($parentid=0)
    {
    	$items = $this->_model->fetchAll(array(
    		'parentid=?'		=>	$parentid
    	),'orderid')->toArray();
    	$retitems = array();
    	foreach ($items as $key=>$item)
    	{
    		$additem = array(
    			'label'		=>	$item['label'],
    			'visible'	=>	$item['visible'],
    		);
    		if ($item['uri'])
    		{
    			$additem['uri'] = $item['uri']?$item['uri']:NULL;
    			$url = trim($_SERVER['REQUEST_URI'],'/');
    			$itemurl = trim($additem['uri'],'/');
    			if (strpos($url,$itemurl)===0) $additem['active'] = 1;
    		}
    		else
    		{
				$additem['module']=$item['module'];
				$additem['controller']=$item['controller'];
				$additem['action']=$item['action'];
				$item['route'] = 'statpage';
				$paramsGroups = explode(';',$item['params']);
				$additem['params'] = array();
				foreach ($paramsGroups as $paramsGroup);
				{
					$paramsKeyValue = explode('=',$paramsGroup);
					if (count($paramsKeyValue)==2)
						$additem['params'][$paramsKeyValue[0]] = $paramsKeyValue[1];
				}
				$controller = $this->getRequest()->getControllerName();
				$action = $this->getRequest()->getActionName();
				if ($controller == $additem['controller'] && count($additem['params'])==0 && $action!=$additem['action'])
					$additem['active'] = 1;
    		}
    			
    		if ($sub = $this->getMenuArray($item['id']))
    		{
    			$additem['pages'] = $sub;
    		}
    		$retitems[] = $additem;
    	}
    	return $retitems;
    }    
        
}
