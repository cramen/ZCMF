<?php

class Z_View_Helper_Admin_Head extends Zend_View_Helper_Abstract
{
	public function admin_head($content)
	{
		$html = '<div class="ui-widget-header ui-corner-tl" id="content-header">'.$content.'</div>';
		return $html;
	}
}

?>