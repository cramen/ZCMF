<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Лицензия
 *
 * Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации
 * (в дальнейшем именуемыми "Программное Обеспечение"),безвозмездно использовать Программное Обеспечение без ограничений,
 * включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам,
 * которым предоставляется данное Программное Обеспечение, соблюдении следующих условий:
 *
 * Вышеупомянутый копирайт и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
 *
 * При копировании, добавлении, изменении, распространении, продаже, публикации и сублицензировании программного обеспечения,
 * авторство может быть только дополнено, но не удалено или изменено на другое.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ,
 * ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И
 * ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА,
 * УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ
 * ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 *
 */

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
        if (isset($_POST['z-ajax-form'])) {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        }

        //сменя лэйаута при аякс вызове
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout()->setLayout('ajax');
        }
        else //если это не аякс запрос, отключаем рентер вьюшки и форвардим на index index
        {
            $action = $this->_request->getActionName();
            $controller = $this->_request->getControllerName();
            if ($action != 'index' || $controller != 'index') {
                $this->_forward('index', 'index');
            }
            $this->disableRenderView();
        }

        $action = $this->_request->getActionName();
        $controller = $this->_request->getControllerName();
        $module = $this->_request->getModuleName();
        if ($action != 'index' && $controller != 'index' && $controller != 'z_user' && $controller != 'z_menu' && $controller != 'error') {
            $role = Z_Auth::getInstance()->getUser()->getRole();
            $acl = Z_Acl::getInstance();
            $allowed = true;
            try {
                $allowed = $acl->isAllowed($role, $controller, $action);
            }
            catch (Exception $e)
            {
                if (Z_Auth::getInstance()->getUser()->getRole() == 'root')
                    Z_FlashMessenger::addMessage('Роль, ресурс или привилегия не существует.');
                $allowed = false;
            }
            //			if ($role=='root') $allowed=true;
            if (!$allowed) {
                $this->_forward('deny', 'error');
            }
            else
            {
                $site = Zend_Registry::get('config')->get('site');
                if ($site && $site->get('adminlog', false)) {
                    Z_Log::info('AdminPanel: ' . Z_Auth::getInstance()->getUser()->getLogin() . ' access to ' . $module . ':' . $controller . ':' . $action, var_export($this->getRequest()->getParams(), true));
                }
            }
        }

        //конфиг
        $this->_config = new Zend_Config($this->getInvokeArg('bootstrap')->getOptions());
        $this->view->config = $this->_config;

        //Аплоад файлов
        $nameSpace = new Zend_Session_Namespace('Z-File-Uploader');
        if ($nameSpace->files) {
            $_FILES = $nameSpace->files;
            $nameSpace->files = NULL;
        }
    }

    public function postDispatch()
    {
        if (isset($_POST['z-ajax-form'])) {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        }
        if ($this->getRequest()->isXmlHttpRequest() || isset($_POST['z-ajax-form'])) {
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

    public function ajaxGo($url)
    {
        jQuery::evalScript('z_ajax_go("' . $url . '")');
    }

    public function ajaxConfirm($question, $confirm_param_name = 'confirmed', array $javaparams = array())
    {
        if ($this->_getParam($confirm_param_name)) {
            return true;
        }
        else
        {
            $params_str = implode(',', $javaparams);

            jQuery::evalScript('
         	    		if (confirm("' . addcslashes($question, '"') . '"))
         	    		{
         	    			z_ajax_go("' . $this->view->url() . '",{' .
                    $confirm_param_name . ':1' .
                    (empty($javaparams) ? '' : ',' . $params_str) .
                    '});
         	    		}
         	    	');

            return false;
        }


    }
}

?>