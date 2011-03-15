<?php

class GalleryController extends Zend_Controller_Action
{
	
	public function indexAction()
	{
		$model = new Site_Model_Gallery();
		$this->view->items = $model->fetchAll(null,'orderid asc');
	}
	
	public function cardAction()
	{
		$modelGroups = new Site_Model_Gallery();
		$model = new Site_Model_Gallery_Items();
		
		$groupId = $this->getRequest()->getParam('group');
		$page = $this->getRequest()->getParam('page',1);
		
		$group = $modelGroups->find($groupId)->current();
		if (!$group)
		{
			$this->_forward('error','error');
			return false;
		}
		
		$select = $model->select('true')->order('orderid asc')->where('gallery_id=?',$group->id);
		
		$adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage(12);
		$paginator->setCurrentPageNumber($page);
		
		$this->view->items = $paginator;
		$this->view->group = $group;		
	}	
	
}

