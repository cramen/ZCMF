<?php

class Z_Admin_Form_Element_Mce extends Z_Admin_Form_Element_Textarea
{
	public $helper = 'FormMce';
	
	public function loadDefaultDecorators()
	{
		if ($this->loadDefaultDecoratorsIsDisabled ())
		{
			return;
		}
		
		if (!$this->id) $this->id='mce_'.rand(1000000,10000000);
		if ($this->class) $this->class .= 'tinymce'; else $this->class = 'tinymce';
		
		$decorators = $this->getDecorators ();
		if (empty($decorators))
		{
	    	$this->setDecorators(array(
	    		array('Errors',array('class'=>Z_Admin_Form::$_errorDecoratorClass)),
	    		array('Description', array ('tag' => 'p','class' => 'z-form-description','escape'=>false)),
	    		'ViewHelper',
	    		array('HtmlTag', array('tag' => 'div','class'=>'z-form-inputItem')),
	    		array('Label'),
	    	));
		}
	}
}
