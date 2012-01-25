<?php

class SearchController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $searchString = trim($this->_getParam('search',''));
        $this->view->searchString = $searchString;

        if ($searchString)
        {
            $res = Z_Search::find($searchString);

            $this->view->result = $res;

        }


    }

}

