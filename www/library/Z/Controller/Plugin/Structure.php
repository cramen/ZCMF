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

class Z_Controller_Plugin_Structure extends Zend_Controller_Plugin_Abstract
{

	/**
	 * 
	 * @var Z_Model_Structure
	 */
	protected $_model = NULL;
	
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
    	if ($this->getRequest()->getParam('module')=='admin') return;
    	
    	$this->_model = new Z_Model_Structure();
    	
    	$items = $this->getMenuArray();
		$navigation = new Zend_Navigation($items);
		Zend_Registry::set('navigation',$navigation);
    }
    
    private function getMenuArray($parentid=0)
    {
    	$items = $this->_model->fetchAll(array(
    		'parentid=?'		=>	$parentid
    	),'orderid')->toArray();
    	$retitems = array();
    	foreach ($items as $key=>$item)
    	{
    		$additem = array(
    			'label'		=>	$item['label'],
    			'visible'	=>	$item['visible'],
    		);
    		if ($item['uri'])
    		{
    			$additem['uri'] = $item['uri']?$item['uri']:NULL;
    			$url = trim($_SERVER['REQUEST_URI'],'/');
    			$itemurl = trim($additem['uri'],'/');
    			if (strpos($url,$itemurl)===0) $additem['active'] = 1;
    		}
    		else
    		{
				$additem['module']=$item['module'];
				$additem['controller']=$item['controller'];
				$additem['action']=$item['action'];
				$item['route'] = 'statpage';
				$paramsGroups = explode(';',$item['params']);
				$additem['params'] = array();
				foreach ($paramsGroups as $paramsGroup);
				{
					$paramsKeyValue = explode('=',$paramsGroup);
					if (count($paramsKeyValue)==2)
						$additem['params'][$paramsKeyValue[0]] = $paramsKeyValue[1];
				}
				$controller = $this->getRequest()->getControllerName();
				$action = $this->getRequest()->getActionName();
				if ($controller == $additem['controller'] && count($additem['params'])==0 && $action!=$additem['action'])
					$additem['active'] = 1;
    		}
    			
    		if ($sub = $this->getMenuArray($item['id']))
    		{
    			$additem['pages'] = $sub;
    		}
    		$retitems[] = $additem;
    	}
    	return $retitems;
    }    
        
}
