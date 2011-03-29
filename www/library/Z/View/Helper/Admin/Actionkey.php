<?php

class Z_View_Helper_Admin_Actionkey extends Zend_View_Helper_Abstract
{
	public function admin_actionkey($href='',$icon='',$params=array())
	{
		if (isset($params['href'])) unset($params['href']);
		
		$addclass='';
		if (isset($params['class']))
		{
			$addclass = $params['class'];
			unset($params['class']);
		}
		$title='';
		if (isset($params['title']))
		{
			$title = $params['title'];
			unset($params['title']);
		}
		$state='ui-state-default';
		if (isset($params['state']))
		{
			$state = $params['state'];
			unset($params['state']);
		}
		
		$addOptions = '';
		foreach ($params as $key=>$value)
		{
			$addOptions .= ' '.$key.'="'.$value.'"';
		}
		
		$html = '<div class="ui-button ui-widget '.$state.' ui-corner-all" style="margin-top: 0; margin-bottom: 0;">
					<a href="'.$href.'" class="ui-icon '.$icon.' '.(isset($params['noajax'])?'':'z-ajax').' '.$addclass.'" title="'.$title.'"'.$addOptions.'></a>
				</div>';
		return $html;
	}
}

?>