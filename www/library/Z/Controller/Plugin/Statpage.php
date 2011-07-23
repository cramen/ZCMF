<?php

class Z_Controller_Plugin_Statpage extends Zend_Controller_Plugin_Abstract
{
    protected static $is_check = false;
	
	public function __construct()
	{
	}
	
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {

    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {

        if ($this->getResponse()->getHttpResponseCode() == 404 && !self::$is_check)
        {
            self::$is_check = true;

            $path = $_SERVER['REQUEST_URI'];
            $path = explode('?',$path,2);
            $path = trim(urldecode($path[0]),'/');

            $sp = new Z_Statpage($path);
            if (!$sp->isError())
            {
                $request->setControllerName('page');
                $request->setActionName('show');
                $request->setParam('page',$sp);
                $request->setDispatched(false);
                $this->getResponse()->setBody('');
                $this->getResponse()->setHttpResponseCode(200);
            }

        }
    }
}
