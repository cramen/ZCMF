<?
class Admin_Z_Config_TreeController extends Z_Admin_Controller_Datacontrol_Abstract
{


    protected function getForm(Z_Admin_Form $form)
    {
        if ($this->_getParam('action')=='edit')
        {

            $currentParam = $this->z_model->find($this->_getParam('id'))->current();
            $type = $currentParam->type;

            if($type=='directory')
            {
                Z_FlashMessenger::addMessage('Нельзя изменять разделы');
                $this->ajaxGo($this->view->url(array('id'=>'null','action'=>'list')));
            }

            if($type=='int')
            {
                $form->addElement(new Z_Admin_Form_Element_Text('value',array(
                    'label'		=>	$currentParam->title,
                    'validators'=>	array(
                        'digits'
                    ),
                )));
            }
            elseif($type=='string')
            {
                $form->addElement(new Z_Admin_Form_Element_Text('value',array(
                    'label'		=>	$currentParam->title,
                )));
            }
            elseif($type=='password')
            {
                $form->addElement(new Z_Admin_Form_Element_Password('value',array(
                    'label'		=>	$currentParam->title,
                    'required'	=>	true
                )));
            }
            elseif($type=='bool')
            {
                $form->addElement(new Z_Admin_Form_Element_Select('value',array(
                    'label'		=>	$currentParam->title,
                    'MultiOptions'=>array('Нет','Да'),
                )));
            }
            elseif($type=='text')
            {
                $form->addElement(new Z_Admin_Form_Element_Textarea('value',array(
                    'label'		=>	$currentParam->title,
                )));
            }
            elseif($type=='file')
            {
                $form->addElement(new Z_Admin_Form_Element_File('value',array(
                    'label'		=>	$currentParam->title,
                    'validators'=>	array(
                        array('Size', false, 1024*1024*5),
                    ),
                )));
            }
            elseif($type=='image')
            {
                $form->addElement(new Z_Admin_Form_Element_File('value',array(
                    'label'		=>	$currentParam->title,
                    'validators'=>	array(
                        array('Size', false, 1024*1024*5),
                        array('IsImage'),
                    ),
                )));
            }
            elseif($type=='html')
            {
                $form->addElement(new Z_Admin_Form_Element_Mce('value',array(
                    'label'		=>	$currentParam->title,
                    'toolbar'	=>	'default',
                    'filemanager'	=>	true,
                )));
            }
        }

    }

}
