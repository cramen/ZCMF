<?
class Admin_Config_TreeController extends Z_Admin_Controller_Datacontrol_Abstract
{


    protected function getTree()
    {
        $arr = $this->z_model->fetchPairs(array('id', 'title'), array('parentid=?' => 0, 'type=?' => 'directory'));
        $arr['0'] = 'Корень';
        return $arr;
    }


}
