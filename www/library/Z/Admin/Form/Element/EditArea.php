<?php

class Z_Admin_Form_Element_EditArea extends Z_Admin_Form_Element_Textarea
{
	public $helper = 'FormEditArea';
	
	public function loadDefaultDecorators()
	{
		if ($this->loadDefaultDecoratorsIsDisabled ())
		{
			return;
		}
		
		if (!$this->id) $this->id='editarea_'.rand(1000000,10000000);
		
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
