<?php

class RobotsController extends Zend_Controller_Action
{

  public function indexAction()
  {
  	$this->_helper->viewRenderer->setNoRender(true);
  	Zend_Layout::getMvcInstance()->disableLayout();
  	$conf = new Z_Config('robots.txt');
  	echo $conf->getValue();
  }

}

