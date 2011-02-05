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
    $sid = $this->getRequest()->getParam('id');
    if ($sid === NULL) $sid = 'error';
    $page = new Z_Statpage($sid);
    if ($page->isError())
    {
      $this->getResponse()->setHttpResponseCode(404);
    }
    $this->view->page = $page;
  }

}

