<?php

class Z_Controller_Plugin_AdminPanel_Plugin_Seo implements Z_Controller_Plugin_AdminPanel_Plugin_Interface
{
	protected $_z_resourceId = 'z_seo';
	
	/**
     * Create Z_Controller_Plugin_AdminPanel_Plugin_Main
     *
     * @param string $tab
     * @paran string $panel
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'seo';
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
    	if (!Z_Acl::getInstance()->isAllowed(Z_Auth::getInstance()->getUser()->getRole(),$this->_z_resourceId)) return;
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
    	if (!Z_Acl::getInstance()->isAllowed(Z_Auth::getInstance()->getUser()->getRole(),$this->_z_resourceId)) return;
    	$view = new Zend_View();
    	
    	$modelSeo = new Z_Model_Titles();
    	$currentItem = $modelSeo->fetchRow(array('uri=?'=>$_SERVER['REQUEST_URI']));
    	if ($currentItem)
    	{
    		$adminUrl = '/admin/z_seo/edit/id/'.$currentItem->id;
    		$adminLinkText = 'Изменить';
    	}
    	else
    	{
    		$adminUrl = '/admin/z_seo/add/uri/'.base64_encode($_SERVER['REQUEST_URI']);
    		$adminLinkText = 'Добавить';
    	}
    		
        return '<h4>Текущие значения:</h4>'.
        	'<strong>URI:</strong> '.$_SERVER['REQUEST_URI'].'<br />'.
        	'<strong>Заголовок:</strong> '.strip_tags($view->headTitle()).'<br />'.
        	'<strong>Мета:</strong> <br />'.nl2br($view->escape($view->headMeta())).'<br />'.
        	'<br /><a href="'.$adminUrl.'" target="_blank">'.$adminLinkText.'</a>';
    }

}