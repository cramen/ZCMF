<?
class Admin_Client_Forms_Elements_ParamsController extends Z_Admin_Controller_Datacontrol_Abstract
{

    public function listAction()
    {
        $model = $this->z_model;
        $form = $this->getFormInstance();
        $data = $this->getRequest()->getPost();

        $row = $model->find($this->getRequest()->getParam('client_forms_elements_params_parentid'))->current();
        if (trim($row->options)) {
            $preoptions = Zend_Json::decode($row->options);
        }
        else
        {
            $preoptions = array();
        }
        $form->setDefaults($preoptions);


        if ($data && $form->isValid($data)) {
            $options = $form->getValidValues($data);

            $row->options = Zend_Json::encode($options);
            $row->save();

            if ($this->getRequest()->getParam('z-ajax-form-applyonly')) {

            }
            else
            {
                $this->ajaxGo($this->view->url(array(
                    'controller' => 'client_forms_elements',
                    'client_forms_elements_params_parentid' => null
                )));
            }

        }

        $this->view->form = $form;
    }


}
