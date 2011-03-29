<?php

class Z_Admin_Form_Element_Date extends Zend_Form_Element_Text
{

    public function clearValidators()
    {
        $this->_validators = array();
        $this->addValidator('date',true,array('format'=>'yyyy-mm-dd'));
        return $this;
    }
	
    public function render(Zend_View_Interface $view = null)
    {
		$this->class = $this->class." ui-widget-content ui-corner-all";
		$this->id = $this->id?$this->id:rand(1000000,10000000);
		$this->format = $this->format?$this->format:'yy-mm-dd';
		
		if (!$this->getValue())
			$this->setValue(date('Y-m-d'));
		
		jQuery::evalScript('$("#'.$this->id.'").datepicker({
    		changeMonth: true,
			changeYear: true,
			dateFormat: "'.$this->format.'"
			});
		');
		
    	return parent::render($view);
    }
    
	public function loadDefaultDecorators()
	{
		if ($this->loadDefaultDecoratorsIsDisabled())
		{
			return;
		}
		
		$decorators = $this->getDecorators();
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
