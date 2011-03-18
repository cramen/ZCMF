<?php

class Z_View_Helper_Template extends Zend_View_Helper_Abstract
{
	public function template($template="",$values=Array())
	{
		$renderer = new Z_View_Template($template,$values);
		return $renderer->render();
	}
}

?>