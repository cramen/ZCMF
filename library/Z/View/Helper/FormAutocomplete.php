<?php

class Z_View_Helper_FormAutocomplete extends Zend_View_Helper_FormText
{
 
    public function FormAutocomplete($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable

        if (!isset($attribs['source']))
        {
        	throw new Exception('Не задан источник для элемента формы "autocomplete" '.$name);
        }
        
        $source = $attribs['source'];
        $minLength = isset($attribs['minLength'])?$attribs['minLength']:1;
        unset($attribs['source']);
        unset($attribs['minLength']);
        
		$script = '
		$("#'.$id.'").autocomplete({
			source: "'.$source.'",
			minLength: '.$minLength.'
		});		
		';
    	jQuery::evalScript($script);

        return parent::formText($name,$value,$attribs);
    }
}
