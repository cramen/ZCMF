<?php

class Z_Controller_Action_Band extends Zend_Controller_Action
{

	/**
	 * 
	 * @var string
	 */
	protected $_model=false;
	protected $_perpage=false;
	protected $_perpage_param='page';
	protected $_id_param='id';
	protected $_orderby=false;
	protected $_conditions=array();
	
	
	public function indexAction()
	{
		$model = new $this->_model;
		$select = $model->select(true);
		
		if ($this->_orderby)
			$select->order($this->_orderby);
			
		if (!empty($this->_conditions))
		{
			foreach ($this->_conditions as $key=>$val)
			{
				$select->where($key,$val);
			}
		}
		
		if ($this->_perpage)
		{
		  	$page = $this->_getParam($this->_perpage_param,1);
		  	
			$adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
			$paginator = new Zend_Paginator($adapter);
			$paginator->setItemCountPerPage($this->_perpage);
			$paginator->setCurrentPageNumber($page);
			$this->view->items = $paginator;
		}
		else
		{
			$this->view->items = $model->fetchAll($select);
		}
		
	}

	public function cardAction()
	{
		$model = new $this->_model;
		$item = $model->find($this->_getParam($this->_id_param,1))->current();
		
		if (!$item)
		{
			$this->_forward('error','error');
		}
		
		$this->view->item = $item;		
	}
	
}