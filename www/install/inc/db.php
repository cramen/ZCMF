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
            //$db = Zend_Db::factory($dbParam);
	    
            try
            {
            
		if (!$conn = mysql_connect($data['host'],$data['username'],$data['password'])) throw new Exception('Connection error');
		if (!mysql_select_db($data['dbname'],$conn)) throw new Exception('Db select error');
		mysql_query('SET NAMES UTF8;');
		
                //$db->query('SHOW TABLES;');
                $file = APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'dump.sql';
                $query = file_get_contents(APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'dump.sql');
                $queries = $this->splitQueries($query);

                foreach($queries as $key => $q)
                {
                    //$db->query($q);
                    if (!mysql_query($q,$conn)) throw new Exception('Dump error');
                }


                $config = file_get_contents(APPLICATION_PATH.'/configs/application.ini');
                $config = preg_replace('/(resources\.db\.params\.host\s*=\s*)(.+)/','$1"'.$data['host'].'"',$config);
                $config = preg_replace('/(resources\.db\.params\.username\s*=\s*)(.+)/','$1"'.$data['username'].'"',$config);
                $config = preg_replace('/(resources\.db\.params\.password\s*=\s*)(.+)/','$1"'.$data['password'].'"',$config);
                $config = preg_replace('/(resources\.db\.params\.dbname\s*=\s*)(.+)/','$1"'.$data['dbname'].'"',$config);
                file_put_contents(APPLICATION_PATH.'/configs/application.ini',$config);
                
                return true;
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

    /**
     * Код этой функции взят из CMS Joomla
     * @return array
     */
	protected function splitQueries($sql)
	{
        $sql = trim($sql);

        $sql = preg_replace('/(--.*\n)+/','',$sql);
        $sql = preg_replace('/\/\*!.+\*\/;/','',$sql);
        $res = preg_split('/;\n/',$sql);

        foreach($res as $key=>$el)
        {
            $res[$key] = trim($el,"\n");
//            echo '::'.$res[$key]."::\n";

            if (substr($el,0,2)=='--') unset($res[$key]);

        }

        return $res;
        
//		$buffer		= array();
//		$queries	= array();
//		$in_string	= false;
//
//		$sql = trim($sql);
//
//		$sql = preg_replace("/\n\#[^\n]*/", '', "\n".$sql);
//
//		for ($i = 0; $i < strlen($sql) - 1; $i ++)
//		{
//			if ($sql[$i] == ";" && !$in_string) {
//				$queries[] = substr($sql, 0, $i);
//				$sql = substr($sql, $i +1);
//				$i = 0;
//			}
//
//			if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
//				$in_string = false;
//			}
//			elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset ($buffer[0]) || $buffer[0] != "\\")) {
//				$in_string = $sql[$i];
//			}
//			if (isset ($buffer[1])) {
//				$buffer[0] = $buffer[1];
//			}
//			$buffer[1] = $sql[$i];
//		}
//
//		if (!empty($sql)) {
//			$queries[] = $sql;
//		}
//
//		return $queries;
	}

}