<?php
/**
 * User: cramen
 * Date: 29.03.11
 * Time: 14:46
 */

class install_db
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
        $this->view->title = "Подключение к БД";
        $this->view->content = $form;

        if($_SERVER['REQUEST_METHOD'] == 'POST' && $form->isValid($_POST))
        {
            $data = $form->getValidValues($_POST);
            $config = new Zend_Config_Ini(APPLICATION_PATH.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'application.ini','production',true);
            $dbParam = $config->resources->db;
            $dbParam->params->host = $data['host'];
            $dbParam->params->username = $data['username'];
            $dbParam->params->password = $data['password'];
            $dbParam->params->dbname = $data['dbname'];
            $db = Zend_Db::factory($dbParam);

            try
            {
                $db->query('SHOW TABLES;');
                $query = file_get_contents(APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'dump.sql');
                $db->query($query);


                $config = file_get_contents(APPLICATION_PATH.'/configs/application.ini');
                $config = preg_replace('/(resources\.db\.params\.host\s*=\s*)(.+)/','$1"'.$data['host'].'"',$config);
                $config = preg_replace('/(resources\.db\.params\.username\s*=\s*)(.+)/','$1"'.$data['username'].'"',$config);
                $config = preg_replace('/(resources\.db\.params\.password\s*=\s*)(.+)/','$1"'.$data['password'].'"',$config);
                $config = preg_replace('/(resources\.db\.params\.dbname\s*=\s*)(.+)/','$1"'.$data['dbname'].'"',$config);
                file_put_contents(APPLICATION_PATH.'/configs/application.ini',$config);
                

                return TRUE;
            }
            catch (Exception $e)
            {
                $this->view->error = $e->getMessage();
            }
        }

        return false;

    }

    /**
     * @return Zend_Form
     */
    protected function getForm()
    {
        $form = new Zend_Form();
        $form->addElement(new Zend_Form_Element_Text('host',array(
            'label'     =>  'Host',
            'value'     =>  'localhost',
            'required'  =>  TRUE,
        )));
        $form->addElement(new Zend_Form_Element_Text('username',array(
            'label'     =>  'Пользователь БД',
            'value'     =>  'root',
        )));
        $form->addElement(new Zend_Form_Element_Text('password',array(
            'label'     =>  'Пароль БД',
            'value'     =>  '',
        )));
        $form->addElement(new Zend_Form_Element_Text('dbname',array(
            'label'     =>  'Имя базы данных',
            'value'     =>  '',
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