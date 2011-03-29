<?php
/**
 * User: cramen
 * Date: 29.03.11
 * Time: 14:46
 */

class install_config
{
    /**
     * @var Zend_View
     */
    protected $view;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function run()
    {
        $form = $this->getForm();
        $this->view->title = "Настройка паролей администратора";
        $this->view->content = $form;

        if($_SERVER['REQUEST_METHOD'] == 'POST' && $form->isValid($_POST))
        {
            $data = $form->getValidValues($_POST);

            $user = new Z_User('admin');
            $user->set('password',$_POST['admin_password']);
            $user->save();

            $user = new Z_User('root');
            $user->set('password',$_POST['root_password']);
            $user->save();

            return TRUE;
        }

        return false;

    }

    /**
     * @return Zend_Form
     */
    protected function getForm()
    {
        $form = new Zend_Form();
        $form->addElement(new Zend_Form_Element_Password('root_password',array(
            'label'     =>  'Пароль суперпользователя (root)',
            'required'  =>  TRUE,
        )));
        $form->addElement(new Zend_Form_Element_Password('admin_password',array(
            'label'     =>  'Пароль администратора (admin)',
            'required'  =>  TRUE,
        )));
        $form->addElement(new Zend_Form_Element_Submit('submit',array(
            'label'     =>  'Дальше',
        )));

        $translator = new Zend_Translate_Adapter_Array(Z_Admin_Form::$_translate_array,'ru_RU');
        $form->setTranslator($translator);

        return $form;
    }

}