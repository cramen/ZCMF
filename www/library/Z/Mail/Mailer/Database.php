<?php

class Z_Mail_Mailer_Database extends Z_Mail_Mailer
{
	
	function __construct($sid,$variables=array(),$options = array())
	{
		$model = new Z_Model_Mails();
		$mail = $model->fetchRow(array('sid=?'=>$sid));
		if (!$mail) throw new Exception('Для ключа "'.$sid.'" не найден наблон письма.');
		$this->set($mail->toArray());
		
		foreach ($this->get() as $key=>$val)
		{
			$tpl = new Z_View_Template($val,$variables);
			$this->set($key,$tpl->render());
			unset($tpl);
		}
		$this->set($options);
	}
}

?>