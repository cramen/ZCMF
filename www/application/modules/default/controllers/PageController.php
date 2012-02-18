<?php


class PageController extends Zend_Controller_Action
{

    public function init()
    {
    }

    public function indexAction()
    {
        $this->_forward('show');
    }

    public function showAction()
    {
        $this->view->page = $this->getRequest()->getParam('page');
    }

}

