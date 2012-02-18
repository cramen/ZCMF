<?
class Admin_Z_LogsController extends Z_Admin_Controller_Datacontrol_Abstract
{

    public function clearAction()
    {
        $model = new Z_Model_Log();
        $model->delete(null);

        $this->disableRenderView();
        $this->ajaxGo($this->view->url(array('action' => 'list')));
    }

}
