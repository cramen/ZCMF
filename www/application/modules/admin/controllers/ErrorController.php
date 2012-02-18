<?php

class Admin_ErrorController extends Z_Admin_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                // 404 error -- controller or action not found
//                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Страница не найдена';
                break;
            default:
                // application error
//                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Ошибка приложения';
                break;
        }

        $this->view->exception = $errors->exception;
        $this->view->request = $errors->request;
    }

    public function denyAction()
    {
        $resources = new Z_Model_Resources();
        $privileges = new Z_Model_Privileges();

        $resource = $resources->fetchRow(array('resourceId=?' => 'admin_' . $this->_getParam('controller')));
        $privilege = $privileges->fetchRow(array('name=?' => $this->_getParam('action')));

        Z_FlashMessenger::addMessage('Доступ к действию данного модуля запрещен.');

        if (Z_Auth::getInstance()->getUser()->getRole() == 'guest') {
            $this->ajaxGo($this->view->url(array('controller' => 'z_user', 'action' => 'login')));
            $this->ajaxGo($this->view->url(array('controller' => 'z_menu', 'action' => 'index')));
            $this->ajaxGo($this->view->url(array('controller' => 'index', 'action' => 'index')));
        }
        else
        {
            if ($privilege)
                Z_FlashMessenger::addMessage('Действие: ' . ($privilege ? $privilege->title : 'Неизвестно'));
            if ($resource)
                Z_FlashMessenger::addMessage('Модуль: ' . ($resource ? $resource->title : 'Неизвестно'));
        }

        $this->disableRenderView();
    }

}

