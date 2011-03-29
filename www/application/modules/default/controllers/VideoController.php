<?php

class VideoController extends Zend_Controller_Action
{
	
	public function indexAction()
	{
		$modelGroups = new Site_Model_Video();
		$model = new Site_Model_Video_Items();
		
		$groupId = $this->getRequest()->getParam('group');
		$page = $this->getRequest()->getParam('page',1);
		
		$group = $modelGroups->find($groupId)->current();
		if (!$group)
		{
			$this->_forward('error','error');
			return false;
		}
		
		$select = $model->select('true')->order('date desc')->where('video_id=?',$group->id);
		
		$adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage(10);
		$paginator->setCurrentPageNumber($page);
		
		$this->view->items = $paginator;
		$this->view->group = $group;
		$this->view->groups = $modelGroups->fetchAll(array('id!=?'=>$group->id),'title');
	}
	
}

