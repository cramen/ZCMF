<?php

class Z_View_Helper_FormAutocompleteId extends Zend_View_Helper_FormText
{
 
    public function FormAutocompleteId($name, $value = null, $attribs = null)
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
		$("#'.$id.'_acString").autocomplete({
			source: "'.$source.'",
			minLength: '.$minLength.',
			select: function(event, ui) {
				$("#'.$id.'").attr("value",ui.item.id);
			}
		});		
		';
    	jQuery::evalScript($script);
    	
    	
    	$realvalue = '';
    	if ($value)
    	{
    		jQuery::evalScript('
    		$.ajax({ url: "'.$source.'?id='.$value.'", success: function(data, textStatus, XMLHttpRequest){
    			$("#'.$id.'_acString").attr("value",data);
      		}});
    		');
    	}
    	
        return parent::formText($name.'_acString',$realvalue,$attribs).parent::formText($name,$value,array('style'=>'display: none;'));
    }
}
