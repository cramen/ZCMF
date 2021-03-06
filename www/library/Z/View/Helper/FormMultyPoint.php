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

class Z_View_Helper_FormMultyPoint extends Zend_View_Helper_FormText
{

    public function FormMultyPoint($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable

        $src = isset($attribs['src']) ? $attribs['src'] : NULL;

        $options = isset($attribs['multiOptions']) ? $attribs['multiOptions'] : array();

        $script = '
$("#container_' . $id . ' .z_multy_point").click(function(){
	$("#container_' . $id . ' .z_multy_point").css("background-color","black");
	$("#container_' . $id . ' .z_multy_point").css("border","1px solid red");
	$(this).css("background-color","red");
	$(this).css("border","1px solid black");
	$("#value_' . $id . '").attr("value",$(this).attr("rel"));
});
        ';


        $divs = '';
        foreach ($options as $key => $el)
        {
            list($x, $y) = explode(';', $el);
            if ($value == $key)
                $divs .= '<div class="z_multy_point" rel="' . $key . '" id="point_' . $key . '_' . $this->view->escape($id) . '" style="width: 7px; height: 7px; position: absolute; background-color: red; border: 1px solid black; margin-top: ' . ($y - 5) . 'px; margin-left:' . ($x - 5) . 'px; cursor:pointer;">&nbsp;</div>';
            else
                $divs .= '<div class="z_multy_point" rel="' . $key . '" id="point_' . $key . '_' . $this->view->escape($id) . '" style="width: 7px; height: 7px; position: absolute; background-color: black; border: 1px solid red; margin-top: ' . ($y - 5) . 'px; margin-left:' . ($x - 5) . 'px; cursor:pointer;">&nbsp;</div>';
        }

        jQuery::evalScript($script);

        // build the element
        $xhtml = '<div id="container_' . $this->view->escape($id) . '">' . $divs
                . '<img src="' . $src . '"'
                . ' id="' . $this->view->escape($id) . '"'
                . $this->_htmlAttribs($attribs)
                . '</img></div>'
                . '<input id="value_' . $this->view->escape($id) . '" type="hidden" name="' . $name . '" value="' . $value . '"></input>';

        return $xhtml;
    }
}
