<?php

class Admin_Z_FileuploaderController extends Zend_Controller_Action
{

	public function indexAction()
	{
	}

	public function uploadAction()
	{
		foreach($_FILES as $key=>$file)
		{
			$new_name = $file['tmp_name'].'_new';
			move_uploaded_file($file['tmp_name'],$new_name);
			$_FILES[$key]['tmp_name'] = $new_name;
		}
		$nameSpace = new Zend_Session_Namespace('Z-File-Uploader');
		$nameSpace->files = $_FILES;
		$this->_helper->viewRenderer->setNoRender(true);
		Zend_Layout::getMvcInstance()->disableLayout();
	}


}
