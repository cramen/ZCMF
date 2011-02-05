<?php

class Z_Admin_Form_Element_AutocompleteId extends Zend_Form_Element_Text
{
	public $helper = 'FormAutocompleteId';
	
	public function loadDefaultDecorators()
	{
		if ($this->loadDefaultDecoratorsIsDisabled ())
		{
			return;
		}
		
//		if (!$this->id) $this->id='autocomplete_'.rand(1000000,10000000);
		$this->class = $this->class." ui-widget-content ui-corner-all";
		
		$decorators = $this->getDecorators ();
		if (empty ( $decorators ))
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
