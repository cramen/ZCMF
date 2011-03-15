<?php

class Site_Form_Feedback extends Zend_Form
{
	public function init()
	{
		$this->addElement(new Zend_Form_Element_Text('fio',array(
			'label'		=>	'Представьтесь',
			'required'	=>	true,
		)));
		$this->addElement(new Zend_Form_Element_Text('email',array(
			'label'			=>	'E-Mail',
			'required'		=>	true,
			'validators'	=>	array('emailAddress')
		)));
		$this->addElement(new Zend_Form_Element_Textarea('text',array(
			'label'			=>	'Текст сообщения',
			'required'		=>	true
		)));
		$this->addElement(new Zend_Form_Element_Captcha('cap', array(
		    'label' =>	"Код подтверждения",
			'style'	=>	'width: 120px;',
		    'captcha' => array(
			'captcha'	=>	'Image',
				'font'		=>	APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'captcha'.DIRECTORY_SEPARATOR.'font.ttf',
				'imgDir'	=>	SITE_PATH.DIRECTORY_SEPARATOR.'captcha',
				'imgUrl'	=>	'/captcha/',		
				'wordLen' 	=>	4,
				'fontSize'		=>	27,
				'height'	=>	65,
				'width'		=>	120,
				'gcFreq'	=>	1,
				'dotNoiseLevel'	=>	0,
				'lineNoiseLevel'	=>	0,
				'expiration'=>	300,
				'timeout'	=>	300,
		    ),
		)));
		$this->addElement(new Zend_Form_Element_Submit('submit',array(
			'label'			=>	'Отправить'
		)));
		
    	$translator = new Zend_Translate_Adapter_Array(Z_Admin_Form::$_translate_array,'ru_RU');
    	$this->setTranslator($translator);
		
	}
}
?>