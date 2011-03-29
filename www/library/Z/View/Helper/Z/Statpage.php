<?php

class Z_View_Helper_Z_Statpage extends Zend_View_Helper_Abstract
{
	public function z_statpage($sid,$part='text')
	{
		$sp = new Z_Statpage($sid);
		if ($part=='title')
		{
			return $sp->getTitle();
		}
		else
		{
			return $sp;
		}
	}
}

?>