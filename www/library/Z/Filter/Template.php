<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Filter_Template implements Zend_Filter_Interface
{
	/**
	 * 
	 * @var Zend_View
	 */
	protected static $view = NULL;
	
    public function filter($value)
    {
		if (NULL === self::$view)    	
    		self::$view = Zend_Controller_Action_HelperBroker::getExistingHelper('ViewRenderer')->view;
    	
        return $this->parse($value);
    }

	protected function parse($template)
	{
		if (Zend_Controller_Front::getInstance()->getRequest()->getModuleName()=='admin') return $template;
		
		preg_match_all('/\{\{(.*?)\}\}/si', $template, $res);
		if (@$res[1]) {
			foreach ($res[1] as $el)
			{
				if (strpos($el, 'action:')===0)
					$template = $this->parseAction($template, $el);
				
				if (strpos($el, 'config:')===0)
					$template = $this->parseConfig($template, $el);
					
			}
		}
		return $template;
	}
    
	
	protected function parseAction($template,$actionstring)
	{
		$actionArray = explode(':', str_replace('action:', '', $actionstring));
		$params = array();
		isset($actionArray[1])?parse_str($actionArray[1],$params):NULL;
		$acmArray = explode('.', $actionArray[0]);
		try {
			$result = self::$view->action(isset($acmArray[0])?$acmArray[0]:NULL,isset($acmArray[1])?$acmArray[1]:NULL,isset($acmArray[2])?$acmArray[2]:NULL,$params);
		}
		catch (Exception $e)
		{
			if (APPLICATION_ENV == 'development')
				$result = $e->getMessage();
			else
				$result = $actionstring;
		}
		return str_ireplace('{{'.$actionstring.'}}', $result, $template);
	}

	protected function parseConfig($template,$actionstring)
	{
		$id = str_replace('config:', '', $actionstring);
		$result = new Z_Config($id);
		return str_ireplace('{{'.$actionstring.'}}', $result, $template);
	}
	
	
}