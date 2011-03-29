<?php

class NewsController extends Zend_Controller_Action
{
	
	public function indexAction()
	{
		$modelGroups = new Site_Model_News_Groups();
		$model = new Site_Model_News();
		
		$groupId = $this->getRequest()->getParam('group');
		$page = $this->getRequest()->getParam('page',1);
		
		$group = false;
		if ($groupId)
		{
			$group = $modelGroups->find($groupId)->current();
			if (!$group)
			{
				$this->_forward('error','error');
				return false;
			}
		}
		
		$select = $model->select('true')->order('date desc');
		if ($group)
		{
			$select->where('group_id=?',$group->id);
		}
		
		$adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage(10);
		$paginator->setCurrentPageNumber($page);
		
		$this->view->items = $paginator;
		$this->view->group = $group;
	}
	
	public function cardAction()
	{
		$id = $this->getRequest()->getParam('id');
		
		$model = new Site_Model_News();
		$modelGroups = new Site_Model_News_Groups();
		
		$this->view->item = $model->find($id)->current();
		
		if ($this->view->item)
		{
			$this->view->group = $modelGroups->find($this->view->item->group_id)->current();
		}
		
	}	
	
}

