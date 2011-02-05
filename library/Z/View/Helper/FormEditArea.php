<?php

class Z_View_Helper_FormEditArea extends Zend_View_Helper_FormTextarea
{
 
    public function FormEditArea($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable

        $syntax = isset($attribs['syntax'])?$attribs['syntax']:'php';
        $height = isset($attribs['height'])?$attribs['height']:300;
        
        
        $script = '
        

        
editAreaLoader.init({
	id : "'.$id.'",
	syntax: "'.$syntax.'",
	start_highlight: true,
	language: "ru",
	allow_resize: "no",
	allow_toggle: false,
	font_size: "10",
	toolbar: "charmap, search, |, undo, redo, |, highlight, reset_highlight, |, syntax_selection, fullscreen, help",
	syntax_selection_allow: "css,html,js,php,xml",
	plugins: "charmap",
	min_height: '.$height.',
	charmap_default: "arrows",
	save_callback: "z_editarea_change_event",
	change_callback: "z_editarea_change_event",
	submit_callback: "z_editarea_change_event"
});
        ';
        
    	jQuery::evalScript($script);

        // build the element
        $xhtml = '<textarea name="' . $this->view->escape($name) . '"'
                . ' id="' . $this->view->escape($id) . '"'
                . $this->_htmlAttribs($attribs) . '>'
                . $value . '</textarea>';

        return $xhtml;
    }
}
