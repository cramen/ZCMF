<?php

class Z_Admin_Form_Element_Checkbox extends Zend_Form_Element_Checkbox
{
    public function __construct($spec, $options = null)
    {
    	parent::__construct($spec, $options);
    	$this->setAttrib('class','z-form-checkbox');
    }	
	public function loadDefaultDecorators()
	{
		if ($this->loadDefaultDecoratorsIsDisabled ())
		{
			return;
		}
		
		$decorators = $this->getDecorators();
		if (empty ( $decorators ))
		{
	    	$this->setDecorators(array(
	    		array('Errors',array('class'=>'z-form-errors ui-state-error')),
	    		array('Description', array ('tag' => 'p','class' => 'z-form-description','escape'=>false)),
	    		'ViewHelper',
	    		array('Label',array('placement'=>'append')),
	    		array('HtmlTag', array('tag' => 'div','class'=>'z-form-inputItem')),
	    	));
		}
	}
}
