<?php

class Z_Admin_Controller_Action extends Zend_Controller_Action
{
	/**
	 * 
	 * @var Zend_Config
	 */
	protected $_config = NULL;
	protected $_target = '#z-content';
	
	public function preDispatch()
	{
		if (isset($_POST['z-ajax-form']))
		{
			$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		}
		
		//сменя лэйаута при аякс вызове
		if ($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->layout()->setLayout('ajax');
		}
		else//если это не аякс запрос, отключаем рентер вьюшки и форвардим на index index
		{
			$action		= $this->_request->getActionName();
			$controller	= $this->_request->getControllerName();
			if ($action!='index' || $controller!='index')
			{
				$this->_forward('index','index');
			}
			$this->disableRenderView();
		}
		
		$action		= $this->_request->getActionName();
		$controller	= $this->_request->getControllerName();
		$module		= $this->_request->getModuleName();
		if ($action!='index' && $controller!='index' && $controller!='z_user' && $controller!='z_menu' && $controller!='error')
		{
			$role = Z_Auth::getInstance()->getUser()->getRole();
			$acl = Z_Acl::getInstance();
			$allowed = true;
			try {
				$allowed = $acl->isAllowed($role,$controller,$action);
			}
			catch (Exception $e)
			{
				if (Z_Auth::getInstance()->getUser()->getRole()=='root')
					Z_FlashMessenger::addMessage('Роль, ресурс или привилегия не существует.');
				$allowed = false;
			}
//			if ($role=='root') $allowed=true;
			if (!$allowed)
			{
				$this->_forward('deny','error');
			}
		}
		
		//конфиг
		$this->_config = new Zend_Config($this->getInvokeArg('bootstrap')->getOptions());
		$this->view->config = $this->_config;
		
		//Аплоад файлов
		$nameSpace = new Zend_Session_Namespace('Z-File-Uploader');
		if ($nameSpace->files)
		{
			$_FILES = $nameSpace->files;
			$nameSpace->files = NULL;
		}
	}
	
	public function postDispatch()
	{
		if (isset($_POST['z-ajax-form']))
		{
			$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		}
		if ($this->getRequest()->isXmlHttpRequest() || isset($_POST['z-ajax-form']))
		{
			$this->view->target = $this->_target;
		}
//		$this->getFrontController()->setDefaultModule($this->getRequest()->getModuleName());
	}
	
	public function dropError($text)
	{
		Z_FlashMessenger::addMessage($text);
//		$this->_helper->viewRenderer->setNoRender(true);
	}

	public function disableRenderView()
	{
		$this->_helper->viewRenderer->setNoRender(true);
	}
	
	public function disableRenderAll()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		Zend_Layout::getMvcInstance()->disableLayout();
	}

	public function setTarget($target)
	{
		$this->_target = $target;
	}
	
	public function ajaxGo($url) {
		jQuery::evalScript('z_ajax_go("'.$url.'")');
	}
}

?>