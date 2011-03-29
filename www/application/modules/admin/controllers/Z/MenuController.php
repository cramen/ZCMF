<?php

class Admin_Z_MenuController extends Z_Admin_Controller_Action
{
	/**
	 * 
	 * @var Z_Model_Menu
	 */
	protected $_model = NULL;
	
	public function init()
	{
		$this->_model = new Z_Model_Resources();
		$this->setTarget('#z-menu');
    	jQuery::evalScript('z_menu_init();');
	}
	
    public function indexAction()
    {
    	$items = $this->getMenuArray();
    	$container = new Zend_Navigation($items);
        $this->view->container = $container;
    }
    
    private function getMenuArray($parentid=0)
    {
    	$items = $this->_model->fetchAll(array(
    		'parentid=?'		=>	$parentid,
    		'visible=?'			=>	1
    	),'orderid')->toArray();
    	
    	$retitems = array();
    	foreach ($items as $key=>$item)
    	{
    		$resource = $item['resourceId'];
    		if (!Z_Acl::getInstance()->has($resource)) continue;
    		$additem = array(
    			'label'		=>	$item['title'],
    			'module'	=>	$this->_getParam('module'),
    			'class'		=>	'z-ajax',
    			'controller'=>	$resource,
    			'action'	=>	$item['actionId'],
    			'resource'	=>	$resource,
    			'privilege'	=>	'view_menu',
    			'visible'	=>	($item['visible']=='1'?true:false),
    		);
    		if ($sub = $this->getMenuArray($item['id']))
    		{
    			$additem['class'] = 'z-admin-menu-path';
    			$additem['pages'] = $sub;
    		}
    		$retitems[] = $additem;
    	}
    	return $retitems;
    }
    
}

