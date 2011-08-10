<?php

class BandsController extends Zend_Controller_Action
{

    /**
     * @var Site_Model_Bands
     */
    protected $modelBands;

    /**
     * @var Site_Model_Band_Items
     */
    protected $modelBandsItems;

    /**
     * @var int
     */
    protected $bandSid;

    /**
     * @var Zend_Db_Table_Row
     */
    protected $bandRow;

    public function init()
    {
        $this->modelBands = new Site_Model_Bands();
        $this->modelBandsItems = new Site_Model_Band_Items();
        $this->bandSid = $this->_getParam('bandsid');
        $this->bandRow = $this->modelBands->fetchRow(array('band_sid=?'=>$this->bandSid));
        if (!$this->bandRow)
        {
            /**
             * Todo
             * нужно правильно вызывать 404 ошибку
             */
            $this->_forward('error','error');
            return;
        }
        $this->view->band = $this->bandRow;
        $this->view->list_url = $this->view->url(array('bandsid'=>$this->bandRow->band_sid),'band');
    }

	public function indexAction()
	{
        $this->_helper->viewRenderer->setRender($this->bandRow->band_template);
        
        $page = $this->_getParam('page',1);

        $select = $this->modelBandsItems->select(true)->where('parentid=?',$this->bandRow->id);
        if ($this->bandRow->band_order == 'orderid')
        {
            $select->order($this->bandRow->band_order.' ASC');
        }
        else
        {
            $select->order($this->bandRow->band_order.' '.$this->bandRow->band_orderdir);
        }

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($this->bandRow->band_perpage?$this->bandRow->band_perpage:-1);
        $paginator->setCurrentPageNumber($page);

        foreach( $paginator as $el )
        {
            $el->card_url = $this->view->url(array('id'=>$el->id,'bandsid'=>$this->bandRow->band_sid),'bandcard');
        }
        $this->view->items = $paginator;
	}
	
	public function cardAction()
	{
        $this->_helper->viewRenderer->setRender($this->bandRow->band_template_card);

        $id = $this->_getParam('id');

        $row = $this->modelBandsItems->find($id)->current();
        if (!$row || $row->parentid != $this->bandRow->id)
        {
            $this->_forward('error','error');
            return;
        }

        $this->view->item = $row;
	}
	
}

