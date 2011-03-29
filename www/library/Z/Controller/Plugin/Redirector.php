<?php

require_once 'Z/Controller/Plugin/AdminPanel/Plugin/Interface.php';

require_once 'Z/Controller/Plugin/AdminPanel/Plugin/Main.php';

class Z_Controller_Plugin_Redirector extends Zend_Controller_Plugin_Abstract
{
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		if (strpos($_SERVER['REQUEST_URI'],'/redirect/')===0)
		{
			$url = preg_replace('/^\/redirect\//','',$_SERVER['REQUEST_URI']);
			header('HTTP/1.1 303 See Other');
			header('Location: http://'.$url);
			exit();
		}
	}

    public function dispatchLoopShutdown()
    {

    	if ($this->getRequest()->isXmlHttpRequest() || isset($_POST['z-ajax-form'])) return;
        if (Zend_Controller_Front::getInstance()->getRequest()->getModuleName()=='admin') return;

        $response = $this->getResponse();
        $html = $response->getBody();
        $html = preg_replace('/href="http:\/\/(.*?)"/e',"'href=\"/redirect/\\1\"'",$html);
        
		$html = str_replace(array('href= "http://','href ="http://','href = "http://'),'href="http://',$html);
        
        $response->setBody($html);
    }
}