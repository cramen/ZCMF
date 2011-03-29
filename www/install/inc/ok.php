<?php
/**
 * Created by JetBrains PhpStorm.
 * User: cramen
 * Date: 29.03.11
 * Time: 14:46
 * To change this template use File | Settings | File Templates.
 */

class install_ok
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
        $this->view->title = "Установка завершена";
        $this->view->content = '
            <div class="errors">Внимание!!!<br />Не забудьте удалить папку install</div>
            <p>Установка завершена. Вы можете перейти на <a href="/">сайт</a> или в <a href="/admin">раздел администрирования</a> сайтом.</p>
            <p>Всю необходимую информацию о фреймворке ZCMF Вы сможете узнать на сайте <a href="http://zcmf.ru">ZCMF</a>.</p>
        ';

        return false;
    }


}