<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    protected function _initRegistry()
    {
        $this->bootstrap('session');

        //конфиг в реестр
        $config = new Zend_Config($this->getApplication()->getOptions(), true);
        Zend_Registry::set('config', $config);

        //берем из сессии прошлый uri и кладем в конфиг
        if (isset($_SERVER['HTTP_HOST'])) {

            $lastUriNamespace = new Zend_Session_Namespace('last_page');

            $uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            if (!$lastUriNamespace->lastUri)
                $lastUriNamespace->lastUri = $uri;

            if (!$lastUriNamespace->currentUri)
                $lastUriNamespace->currentUri = $uri;

            if ($uri != $lastUriNamespace->currentUri)
            {
                $lastUriNamespace->lastUri = $lastUriNamespace->currentUri;
                $lastUriNamespace->currentUri = $uri;
            }

            $config->lastUri = $lastUriNamespace->lastUri;

        }
    }

    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Site',
            'basePath' => APPLICATION_PATH,
        ));
        return $autoloader;
    }

    protected function _initLocale()
    {
        setlocale(LC_ALL, "ru_RU.UTF-8");
    }

    protected function _initMeta()
    {
        $this->bootstrap('view');

        /**
         * @var Zend_View
         */
        $view = $this->getResource('view');
        /**
         * Этот код добавляет мета тег о генераторе сайта.
         */
        $view->headMeta()->appendName(base64_decode('Z2VuZXJhdG9y'), base64_decode('WkNNRiB2ZXJzaW9uIA==') . eval(base64_decode('cmV0dXJuIFpfVmVyc2lvbjo6JHZhbHVlOw==')));
    }

}
