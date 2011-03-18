<?
class Admin_MenuController extends Z_Admin_Controller_Datacontrol_Abstract
{

	public function editPreset($param)
	{
		$param['page'] = $param['url'];
		return $param;
	}

}
