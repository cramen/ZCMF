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

class Z_Controller_Plugin_AdminPanel_Plugin_Seo implements Z_Controller_Plugin_AdminPanel_Plugin_Interface
{
	protected $_z_resourceId = 'z_seo';
	
	/**
     * Create Z_Controller_Plugin_AdminPanel_Plugin_Main
     *
     * @param string $tab
     * @paran string $panel
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'seo';
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
    	if (!Z_Acl::getInstance()->isAllowed(Z_Auth::getInstance()->getUser()->getRole(),$this->_z_resourceId)) return;
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
    	if (!Z_Acl::getInstance()->isAllowed(Z_Auth::getInstance()->getUser()->getRole(),$this->_z_resourceId)) return;
    	$view = new Zend_View();
    	
    	$modelSeo = new Z_Model_Titles();
    	$currentItem = $modelSeo->fetchRow(array('uri=?'=>$_SERVER['REQUEST_URI']));
    	if ($currentItem)
    	{
    		$adminUrl = '/admin/z_seo/edit/id/'.$currentItem->id;
    		$adminLinkText = 'Изменить';
    	}
    	else
    	{
    		$adminUrl = '/admin/z_seo/add/uri/'.base64_encode($_SERVER['REQUEST_URI']);
    		$adminLinkText = 'Добавить';
    	}
    		
        return '<h4>Текущие значения:</h4>'.
        	'<strong>URI:</strong> '.$_SERVER['REQUEST_URI'].'<br />'.
        	'<strong>Заголовок:</strong> '.strip_tags($view->headTitle()).'<br />'.
        	'<strong>Мета:</strong> <br />'.nl2br($view->escape($view->headMeta())).'<br />'.
        	'<br /><a href="'.$adminUrl.'" target="_blank">'.$adminLinkText.'</a>';
    }

}