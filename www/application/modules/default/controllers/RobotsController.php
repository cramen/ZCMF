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

    public function sitemapxmlAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Layout::getMvcInstance()->disableLayout();

        $modelNames = Z_Db_Model_Generator::getAllModels();

        $sitemap = new Z_Sitemap_Xml();

        foreach($modelNames as $modelName)
        {
            if (method_exists($modelName,'ZSitemapXml'))
            {
                $object = new $modelName();
                $sitemap->addUrls($object->ZSitemapXml());
            }
        }
        echo $sitemap->getMap();

    }

}

