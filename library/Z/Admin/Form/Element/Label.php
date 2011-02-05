<?php

class Z_Admin_Form_Element_Label extends Zend_Form_Element_Text
{
	
	public function loadDefaultDecorators()
	{
		if ($this->loadDefaultDecoratorsIsDisabled ())
		{
			return;
		}
		
		$decorators = $this->getDecorators ();
		if (empty ( $decorators ))
		{
	    	$this->setDecorators(array(
	    		array('Label'),
	    		array(array('tag1'=>'HtmlTag'), array('tag' => 'div','class'=>'z-admin-label')),
	    		array(array('tag2'=>'HtmlTag'), array('tag' => 'div','class'=>'clear','placement'=>'append')),
	    	));
		}
	}

}
