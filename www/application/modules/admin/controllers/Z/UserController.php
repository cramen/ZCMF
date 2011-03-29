<?php

class Admin_Z_UserController extends Z_Admin_Controller_Action
{

	public function init()
	{
		$this->setTarget("#z-login");		
	}
	
    public function indexAction()
    {
    }

    public function logoutAction()
    {
    	$form = new Z_Admin_Form;
    	
    	$form->setElementDecorators(array(
    		'ViewHelper',
    	));
    	$form->addElement('hidden', 'logout',array(
    	));
    	$form->addElement('submit', 'submit',array(
    		'label'		=>	'Выйти',
    		'class'		=>	'ui-state-default ui-corner-bl submit',
    		'onMouseOver'	=>	'$(this).addClass("ui-state-hover ui-state-active")',
    		'onMouseOut'	=>	'$(this).removeClass("ui-state-hover ui-state-active")'
    	));
    	
    	if (isset($_POST['logout']))
    	{
    		Z_Auth::getInstance()->logout();
    		jQuery::evalScript('z_menu_show();');
    		$this->ajaxGo('/'.$this->getRequest()->getModuleName().'/');
    		$this->_forward('login');
    		return;
    	}
    	
    	$this->view->form = $form;
    }
    
    
    public function loginAction()
    {
    	$form = new Z_Admin_Form;
    	$form->setElementDecorators(array('ViewHelper'));
    	
    	$form->addElement('text', 'login',array(
    		'required'	=>	true,
			'class'		=>	'ui-state-active ui-corner-bottom z-login-input',
    	));
    	$form->addElement('Password', 'password',array(
    		'required'	=>	true,
			'class'		=>	'ui-state-active ui-corner-bottom z-login-input'
    	));
    	$form->addElement('Checkbox', 'remember',array());
    	$form->addElement('Submit', 'submit',array(
    		'label'		=>	'Войти',
    		'class'		=>	'ui-state-default ui-corner-bl submit',
    		'onMouseOver'	=>	'$(this).addClass("ui-state-hover ui-state-active")',
    		'onMouseOut'	=>	'$(this).removeClass("ui-state-hover ui-state-active")'
    	));
    	
    	if ($_POST && !isset($_POST['logout']))
    	{
    		if ($form->isValid($_POST))
    		{
    			$data = $form->getValues();
    			if (Z_Auth::getInstance()->login($data['login'],$data['password'],$data['remember']?true:false))
    			{
    				jQuery::evalScript('z_menu_show();');
    				$this->ajaxGo('/'.$this->getRequest()->getModuleName().'/');
    			}
    			else
    			{
    				Z_FlashMessenger::addMessage('Логин или пароль не верны');
    			}
    		}
    		else
    		{
    			Z_FlashMessenger::addMessage('Введите логин и пароль');
    		}
    	}

    	if (Z_Auth::getInstance()->getUser()->getLogin()!='guest')
    	{
    		$this->_forward('logout');
    		return;
    	}
    	
    	
    	$this->view->form = $form;
    }

}

