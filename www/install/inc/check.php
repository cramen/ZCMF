<?php
/**
 * User: cramen
 * Date: 29.03.11
 * Time: 14:46
 */

class install_check
{
    /**
     * @var Zend_View
     */
    protected $view;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function run()
    {
        $this->view->title = "Проверка конфигурации сервера";

        $ok = true;

        if ($this->view->files = $this->checkWrite())
        {
            $ok = false;
        }

        $this->view->content = $this->view->render('checkfiles.phtml');

        return $ok;

    }

    protected function checkWrite()
    {
        $files = array(
            APPLICATION_PATH.'/configs/application.ini',
            APPLICATION_PATH.'/data/cache',
            APPLICATION_PATH.'/data/lucene',
            APPLICATION_PATH.'/data/session',
            APPLICATION_PATH.'/models',
            APPLICATION_PATH.'/modules/admin/controllers',
            SITE_PATH.'/storage',
            SITE_PATH.'/captcha',
            SITE_PATH.'/upload',
        );

        $res = array();

        foreach($files as $file)
        {
            if (!is_writable($file)) $res[] = $file;
        }
        return $res;

    }

}