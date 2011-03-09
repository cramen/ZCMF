<?php

class Z_View_Helper_Admin_Breadcrumbs extends Zend_View_Helper_Abstract
{
	public function admin_breadcrumbs($array)
	{
//		echo "<pre>";
//		print_r($array);
//		echo "</pre>";
		$ret = array();
		foreach ($array as $el)
		{
			if (!empty($el['url_array']))
			{
				$strEl = '<a class="z-ajax" href="'.$this->view->url($el['url_array']).'" title="'.$el['description'].'">'.$el['title'].'</a>';
			}
			else
			{
				$strEl = $el['title'];
			}
			$strEl .= $el['description']?':'.$el['description']:'';
			
			$ret[] = $strEl;
		}
		
		$retStr = implode(' - ',$ret);
		
		return $retStr;
	}
}

?>