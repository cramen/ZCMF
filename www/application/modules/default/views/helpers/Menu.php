<?php
///**
// *
// * @author cramen
// * @version
// */


/**
 * Menu helper
 *
 * @uses viewHelper Zend_View_Helper
 */
class Zend_View_Helper_Menu
{
    /**
     * @var Zend_View_Interface 
     */
    public $view;

    /**
     * @return string
     */
    public function menu ()
    {
        $model = new Site_Model_Menu();
        $items = $model->fetchAll(null,'orderid');
        $activeItem = $model->fetchRow(array('"'.addcslashes($this->view->url(),"'").'" LIKE CONCAT(url,"%")'),'LENGTH(url) desc');

        $this->view->items = $items;
        if ($activeItem)
            $this->view->activeId = $activeItem->id;

        return $this->view->render('menu.phtml');
    }

    /**
     * Sets the view field
     * @param $view Zend_View_Interface
     */
    public function setView (Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}
