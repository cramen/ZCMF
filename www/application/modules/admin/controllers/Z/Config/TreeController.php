<?
class Admin_Z_Config_TreeController extends Z_Admin_Controller_Datacontrol_Abstract
{

    public function editSuccess($param)
    {
        if ($param['type']=='directory')
        {
            $elements = $this->z_model->fetchAll(array('parentid=?'=>$this->_getParam('id')),'orderid asc');
            foreach ($elements as $el)
            {
                $currentValue = $param['value_'.$el->id];
                $elements = $this->z_model->update(array('value'=>$currentValue),array('id=?'=>$el->id));
            }
        }
    }


    protected function getForm(Z_Admin_Form $form)
    {
        if ($this->_getParam('action')=='edit')
        {

            $currentParam = $this->z_model->find($this->_getParam('id'))->current();


            if($currentParam->type=='directory')
            {
                $form->addElement(new Z_Admin_Form_Element_Label('confLabel',array(
                    'label'		=>	$currentParam->title,
                )));


                $elements = $this->z_model->fetchAll(array('parentid=?'=>$this->_getParam('id')),'orderid asc');
                foreach ($elements as $el)
                {
                    $this->addConfigFormElement($form, $el->type, $el->title,array(
                        'name'      =>  'value_'.$el->id,
                        'value'     =>  $el->value,
                        'required'  =>  $el->required,
                    ));
                }
            }
            else
            {
                $this->addConfigFormElement($form, $currentParam->type, $currentParam->title);
            }


        }

    }

    protected function addConfigFormElement(Z_Admin_Form $form, $type, $title, $params = array())
    {
        $required   = isset($params['required'])?$params['required']:false;
        $name      = isset($params['name'])?$params['name']:'value';
        $value      = isset($params['value'])?$params['value']:null;


        if($type=='int')
        {
            $form->addElement(new Z_Admin_Form_Element_Text($name,array(
                'label'		=>	$title,
                'validators'=>	array(
                    'digits'
                ),
                'value'     =>  $value,
                'required'     =>  $required,
            )));
        }
        elseif($type=='string')
        {
            $form->addElement(new Z_Admin_Form_Element_Text($name,array(
                'label'		=>	$title,
                'value'     =>  $value,
                'required'     =>  $required,
            )));
        }
        elseif($type=='password')
        {
            $form->addElement(new Z_Admin_Form_Element_Password($name,array(
                'label'		=>	$title,
                'required'	=>	true,
                'value'     =>  $value,
                'required'     =>  $required,
            )));
        }
        elseif($type=='bool')
        {
            $form->addElement(new Z_Admin_Form_Element_Select($name,array(
                'label'		=>	$title,
                'MultiOptions'=>array('Нет','Да'),
                'value'     =>  $value,
                'required'     =>  $required,
            )));
        }
        elseif($type=='text')
        {
            $form->addElement(new Z_Admin_Form_Element_Textarea($name,array(
                'label'		=>	$title,
                'value'     =>  $value,
                'required'     =>  $required,
            )));
        }
        elseif($type=='file')
        {
            $form->addElement(new Z_Admin_Form_Element_File($name,array(
                'label'		=>	$title,
                'validators'=>	array(
                    array('Size', false, 1024*1024*5),
                ),
                'value'     =>  $value,
                'required'     =>  $required,
            )));
        }
        elseif($type=='image')
        {
            $form->addElement(new Z_Admin_Form_Element_File($name,array(
                'label'		=>	$title,
                'validators'=>	array(
                    array('Size', false, 1024*1024*5),
                    array('IsImage'),
                ),
                'value'     =>  $value,
                'required'     =>  $required,
            )));
        }
        elseif($type=='html')
        {
            $form->addElement(new Z_Admin_Form_Element_Mce($name,array(
                'label'		=>	$title,
                'toolbar'	=>	'default',
                'filemanager'	=>	true,
                'value'     =>  $value,
                'required'     =>  $required,
            )));
        }
    }


}
