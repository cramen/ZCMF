<?php

class Z_View_Helper_FormPointPicker extends Zend_View_Helper_FormText
{
 
    public function FormPointPicker($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable
        
        $src = isset($attribs['src'])?$attribs['src']:NULL;
        
        @list($x,$y) = explode(';',$value);
        $x = $x?$x:0;
        $y = $y?$y:0;
        
        $script = '
$("#'.$id.'").click(function(e){
	var x = e.pageX - $(this).offset().left;
	var y = e.pageY - $(this).offset().top;
	$("#point_'.$id.'").css("margin-left",x-4+"px");
	$("#point_'.$id.'").css("margin-top",y-4+"px");
	$("#coords_'.$id.'").attr("value",x+";"+y);
});
        ';

        jQuery::evalScript($script);
        
        // build the element
        $xhtml = '<div><div id="point_' . $this->view->escape($id) . '" style="width: 5px; height: 5px; position: absolute; background-color: red; border: 1px solid black; margin-top: '.($y-4).'px; margin-left:'.($x-4).'px;">&nbsp;</div>'
        		. '<img src="' . $src . '"'
                . ' id="' . $this->view->escape($id) . '"'
                . $this->_htmlAttribs($attribs)
                . '</img></div>'
                . '<input id="coords_' . $this->view->escape($id) . '" type="hidden" name="'.$name.'" value="'.$value.'"></input>';
                
        return $xhtml;
    }
}
