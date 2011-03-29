<?php

class Admin_Z_StatpageController extends Z_Admin_Controller_Datacontrol_Abstract
{

	public function addOverride($param)
	{
		if (!array_key_exists('sid',$param))
			$param['sid'] = Z_Transliterator::translateCyr($param['title']);
		return $param;
	}

	public function editOverride($param)
	{
		return $param;
	}
	
}

