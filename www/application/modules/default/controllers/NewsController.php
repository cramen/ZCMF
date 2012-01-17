<?php

class NewsController extends Zend_Controller_Action
{

    public function listAction()
    {
        $modelNews = new Site_Model_News();

        $theme  = $this->_getParam('theme',null);
        $page   = $this->_getParam('page',1);

        $select = $modelNews->select(true)->order(array('date desc','id desc'));
        if ($theme)
        {
            if (is_array($theme)) $select->where('theme_id in (?)',$theme);
            else $select->where('theme_id = ?',$theme);
        }

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(5);
        $paginator->setCurrentPageNumber($page);

        $this->view->items = $paginator;
        $this->view->themes = Site_Model_News_Themes::getPairs();
        $this->view->theme = $theme;
    }

    public function cardAction()
    {
        $id  = $this->_getParam('id',null);
        $modelNews = new Site_Model_News();

        $this->view->item = $modelNews->find($id)->current();
        $this->view->themes = Site_Model_News_Themes::getPairs();

    }

}

