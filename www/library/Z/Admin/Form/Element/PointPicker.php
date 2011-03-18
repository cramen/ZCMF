<?php

require_once 'Zend/Form/Element/Image.php';

class Z_Admin_Form_Element_PointPicker extends Zend_Form_Element_Image
{

	public $helper = 'formPointPicker';

    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
	    	$this->setDecorators(array(
	    		array('Errors',array('class'=>Z_Admin_Form::$_errorDecoratorClass)),
	    		array(array('cleardiv' => 'HtmlTag'), array('tag' => 'div','class'=>'clear','placement'=>'append')),
	    		array('Description', array ('tag' => 'p','class' => 'z-form-description','escape'=>false)),
	    		'ViewHelper',
	    		array('HtmlTag', array('tag' => 'div','class'=>'z-form-inputItem')),
	    		array('Label'),
	    	));
        }
    }
    
}
