<?php

class Z_Admin_Form_Element_Submit extends Zend_Form_Element_Submit
{

    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }
		
        $this->class = $this->class." ui-button ui-state-default ui-corner-all z-button";
        
        $decorators = $this->getDecorators();
        if (empty($decorators)) {
	    	$this->setDecorators(array(
//	    		'Tooltip',
	    		'ViewHelper',
//	    		'DtDdWrapper',
	    	));
//            $this->addDecorator('Tooltip')
//                 ->addDecorator('ViewHelper')
//                 ->addDecorator('DtDdWrapper');
        }
    }
}
