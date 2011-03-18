<?php

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