<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Лицензия
 *
 * Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации
 * (в дальнейшем именуемыми "Программное Обеспечение"),безвозмездно использовать Программное Обеспечение без ограничений,
 * включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам,
 * которым предоставляется данное Программное Обеспечение, соблюдении следующих условий:
 *
 * Вышеупомянутый копирайт и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
 *
 * При копировании, добавлении, изменении, распространении, продаже, публикации и сублицензировании программного обеспечения,
 * авторство может быть только дополнено, но не удалено или изменено на другое.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ,
 * ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И
 * ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА,
 * УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ
 * ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 *
 */

class Z_View_Helper_FormMce extends Zend_View_Helper_FormTextarea
{

    protected $_mce_default_toolbar = 'default';
    protected $_mce_toolbar = array(
        'full' => '
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,spellchecker",
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
			theme_advanced_buttons2 : "cut,copy,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,|,insertdate,inserttime,preview,|,forecolor,backcolor,spellchecker",
			theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,iespell,media,advhr,|,ltr,rtl,|,fullscreen",
			theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,pagebreak",
		',
        'default' => '
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,spellchecker",
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect",
			theme_advanced_buttons2 : "cut,copy,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,spellchecker",
			theme_advanced_buttons3 : "tablecontrols,|,removeformat,|,sub,sup,|,fullscreen,pagebreak",
			theme_advanced_buttons4 : "",
		',
        'advanced' => '
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,spellchecker",
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect",
			theme_advanced_buttons2 : "cut,copy,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,cleanup,spellchecker",
			theme_advanced_buttons3 : "tablecontrols,|,removeformat,|,sub,sup,|,fullscreen,pagebreak",
			theme_advanced_buttons4 : "",
		',
        'simple' => '
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,spellchecker",
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontsizeselect",
			theme_advanced_buttons2 : "cut,copy,pasteword,|,search,replace,|,undo,redo,|,sub,sup,|,fullscreen,spellchecker,pagebreak",
			theme_advanced_buttons3 : "",
			theme_advanced_buttons4 : "",
		',
        'minimal' => '
			plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,spellchecker",
			theme_advanced_buttons1 : "bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,undo,redo,|,sub,sup,|,fullscreen,spellchecker,pagebreak",
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
        $filemanager = isset($attribs['filemanager']) ? $attribs['filemanager'] : true;
        try
        {
            $allowFileManager = $acl->isAllowed($role, 'filemanager');
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


        $toolbar = isset($attribs['toolbar']) ? $attribs['toolbar'] : $this->_mce_default_toolbar;
        $toolbar = isset($this->_mce_toolbar[$toolbar]) ? $toolbar : $this->_mce_default_toolbar;


        $script = '$("#' . $id . '").tinymce({
    		theme : "advanced",
    		language : "ru",
    		' . $this->_mce_toolbar[$toolbar] . '
			' . ($filemanager ? $filemanagerScript : '') . '
			' . (isset($attribs['content_css']) ? 'content_css : "' . $attribs['content_css'] . '",' : '') . '
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			theme_advanced_resize_horizontal : false,
			theme_advanced_font_sizes: "10px,12px,13px,14px,16px,18px,20px,22px,24px,26px,28px,30px",
			extended_valid_elements : "iframe[name|src|framespacing|border|frameborder|scrolling|title|height|width|style],object[declare|classid|codebase|data|type|codetype|archive|standby|height|width|usemap|name|tabindex|align|border|hspace|vspace],div[id|style|class]",
			media_strict: false,
			' . (isset($attribs['body_class']) ? 'body_class : "' . $attribs['body_class'] . '",' : '') . '
//			force_br_newlines : true,
//			force_p_newlines : false,
//        	forced_root_block : "",			
			width: "100%",
			height: "' . (isset($attribs['height']) ? $attribs['height'] : '300px') . '",
			onchange_callback: "z_mce_save",
			execcommand_callback: "z_mce_save_event",
			remove_script_host: true,
			relative_urls: false,
			spellchecker_languages : "+Russian=ru,Ukrainian=uk,English=en",
			spellchecker_rpc_url : "/sys/tinyspell.php",
			spellchecker_word_separator_chars : \'\\s!"#$%&()*+,./:;<=>?@[\]^_{|}\xa7 \xa9\xab\xae\xb1\xb6\xb7\xb8\xbb\xbc\xbd\xbe\u00bf\xd7\xf7\xa4\u201d\u201c\'
    	});';

        jQuery::evalScript($script);
        unset($attribs['toolbar']);

        $entity_from = array('&amp;', '&lt;', '&gt;', '&nbsp;', '&quot;');
        $entity_to = array('&amp;amp;', '&amp;lt;', '&amp;gt;', '&amp;nbsp;', '&amp;quot;');
        $value = str_replace($entity_from, $entity_to, $value);

        // build the element
        $xhtml = '<textarea name="' . $this->view->escape($name) . '"'
                . ' id="' . $this->view->escape($id) . '"'
                . $this->_htmlAttribs($attribs) . '>'
                . $value . '</textarea><a href="#" class="" onclick="tinymce.execCommand(\'mceToggleEditor\',false,\'' . $id . '\');">Вкл/Выкл редактор.</a>';

        return $xhtml;
    }
}
