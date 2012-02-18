<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Лицензия
 *
 * Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации
 * (в дальнейшем именуемыми "Программное Обеспечение"),безвозмездно использовать Программное Обеспечение без ограничений,
 * включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам,
 * которым предоставляется данное Программное Обеспечение, соблюдении следующих условий:
 *
 * Вышеупомянутый копирайт и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
 *
 * При копировании, добавлении, изменении, распространении, продаже, публикации и сублицензировании программного обеспечения,
 * авторство может быть только дополнено, но не удалено или изменено на другое.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ,
 * ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И
 * ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА,
 * УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ
 * ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 *
 */

class Z_Controller_Plugin_Cache extends Zend_Controller_Plugin_Abstract
{
    protected $is_cached = false;

    public function __construct()
    {

    }


    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if (!$this->canCache()) return;

        $cache = Z_Cache::getInstance();
        $url = str_replace('=', '_', base64_encode($_SERVER['REQUEST_URI']));

        $this->canCacheThisPage();

        if ($this->canCache() && $this->canCacheThisPage() && $body = $cache->load($url)) {
            $this->_request->setControllerName('dummy');
            $this->_request->setActionName('cache');
            $this->getResponse()->setBody($body);
            $this->is_cached = true;
        }
    }

    public function dispatchLoopShutdown()
    {
        if (!$this->canCache()) return;

        $response = $this->getResponse();

        if ($this->canCache() && $this->canCacheThisPage() && $response->getHttpResponseCode() == 200) {
            $cache = Z_Cache::getInstance();
            $url = str_replace('=', '_', base64_encode($_SERVER['REQUEST_URI']));
            $data = $response->getBody();
            $cache->save($data, $url);
        }
    }

    protected function canCache()
    {
        $request = $this->getRequest();
        return $request->isGet() && $request->getModuleName() != 'admin';
    }


    protected function canCacheThisPage()
    {
        $request = $this->getRequest();
        $cache = Z_Cache::getInstance();

        if (!$cacheArray = $cache->load('nocache_pages_array')) {
            $model = new Z_Model_Nocachepages();
            $dbarray = $model->fetchAll()->toArray();
            $cacheArray = array();
            foreach ($dbarray as $dbrow)
            {
                $newrow = eval($dbrow['code']);
                $cacheArray[$dbrow['sid']] = $newrow;
            }
        }

        $params = $request->getParams();
        foreach ($cacheArray as $condArray)
        {
            $condRelease = true;
            foreach ($condArray as $key => $val)
            {
                if (!(isset($params[$key]) && $params[$key] == $val)) {
                    $condRelease = false;
                }
            }
            if ($condRelease) return false;
        }

        return true;
    }
}
