<?php

class Z_View_Helper_FormMce extends Zend_View_Helper_FormTextarea
{

	protected $_mce_default_toolbar = 'default';
	protected $_mce_toolbar = array(
	'full'		=>	'
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
			theme_advanced_buttons2 : "cut,copy,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
			theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,iespell,media,advhr,|,ltr,rtl,|,fullscreen",
			theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking",
		',
	'default'	=>	'
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect",
			theme_advanced_buttons2 : "cut,copy,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,cleanup,code",
			theme_advanced_buttons3 : "tablecontrols,|,removeformat,|,sub,sup,|,fullscreen",
			theme_advanced_buttons4 : "",
		',
	'advanced'	=>	'
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect",
			theme_advanced_buttons2 : "cut,copy,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,cleanup",
			theme_advanced_buttons3 : "tablecontrols,|,removeformat,|,sub,sup,|,fullscreen",
			theme_advanced_buttons4 : "",
		',
	'simple'	=>	'
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontsizeselect",
			theme_advanced_buttons2 : "cut,copy,pasteword,|,search,replace,|,undo,redo,|,sub,sup,|,fullscreen",
			theme_advanced_buttons3 : "",
			theme_advanced_buttons4 : "",
		',
	'minimal'	=>	'
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
			theme_advanced_buttons1 : "bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,undo,redo,|,sub,sup,|,fullscreen",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			theme_advanced_buttons4 : "",
		',
	);

	public function FormMce($name, $value = null, $attribs = null)
	{
		$info = $this->_getInfo($name, $value, $attribs);
		extract($info); // name, value, attribs, options, listsep, disable

		$role = Z_Auth::getInstance()->getUser()->getRole();
		$acl = Z_Acl::getInstance();
		$filemanager = isset($attribs['filemanager'])?$attribs['filemanager']:true;
		try
		{
			$allowFileManager = $acl->isAllowed($role,'filemanager');
		}
		catch (Exception $e)
		{
			$allowFileManager = false;
		}
		$filemanager = $filemanager && $allowFileManager;
		$filemanagerScript = '
mode : "textareas",
file_browser_callback: function(field_name, url, type, win) {
    aFieldName = field_name, aWin = win;
    if($("#elfinder").length == 0)
    {
        $("body").append($("<div/>").attr("id", "elfinder"));
        $("#elfinder").elfinder({
            url : "/sys/elfinder/connectors/php/connector.php",
            lang: "ru",
            dialog : { width: 800, modal: true, title: "Файловый менеджер", zIndex: 400001 }, // open in dialog window
            editorCallback: function(url)
            {
        	aWin.document.forms[0].elements[aFieldName].value = url;
            },
            closeOnEditorCallback: true
        });
    }
    else
    {
	$("#elfinder").elfinder("open");
    }
},
        ';


		$toolbar = isset($attribs['toolbar'])?$attribs['toolbar']:$this->_mce_default_toolbar;
		$toolbar = isset($this->_mce_toolbar[$toolbar])?$toolbar:$this->_mce_default_toolbar;


		$script = '$("#'.$id.'").tinymce({
    		theme : "advanced",
    		language : "ru",
    		'.$this->_mce_toolbar[$toolbar].'
			'.($filemanager?$filemanagerScript:'').'
			'.(isset($attribs['content_css'])?'content_css : "'.$attribs['content_css'].'",':'').'
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			theme_advanced_resize_horizontal : false,
			extended_valid_elements : "iframe[name|src|framespacing|border|frameborder|scrolling|title|height|width|style],object[declare|classid|codebase|data|type|codetype|archive|standby|height|width|usemap|name|tabindex|align|border|hspace|vspace],div[id|style|class]",
			media_strict: false,
//			force_br_newlines : true,
//			force_p_newlines : false,
//        	forced_root_block : "",			
			width: "100%",
			height: "'.(isset($attribs['height'])?$attribs['height']:'300px').'",
			onchange_callback: "z_mce_save",
			execcommand_callback: "z_mce_save_event",
			remove_script_host: true,
			relative_urls: false
    	});';

		jQuery::evalScript($script);
		unset($attribs['toolbar']);

		// build the element
		$xhtml = '<textarea name="' . $this->view->escape($name) . '"'
		. ' id="' . $this->view->escape($id) . '"'
		. $this->_htmlAttribs($attribs) . '>'
		. $value . '</textarea><a href="#" class="" onclick="tinymce.execCommand(\'mceToggleEditor\',false,\''.$id.'\');">Вкл/Выкл редактор.</a>';

		return $xhtml;
	}
}
