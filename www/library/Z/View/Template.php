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

class Z_View_Template
{

    protected $_template = "";
    protected $_values = array();


    function __construct($template = "", $values = array())
    {
        $this->setTemplate($template);
        $this->setValues($values);
    }

    /**
     *
     * @param unknown_type $template
     * @return Z_View_Template
     */
    public function setTemplate($template)
    {
        $this->_template = $template;
        return $this;
    }

    /**
     *
     * @param unknown_type $values
     * @return Z_View_Template
     */
    public function setValues($values)
    {
        if ($values instanceof Zend_Config || $values instanceof Zend_Db_Table_Row || $values instanceof Zend_Db_Table_Rowset)
            $values = $values->toArray();
        $this->_values = $values;
        return $this;
    }

    /**
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return Z_View_Template
     */
    public function setValue($key, $value)
    {
        $this->_values[$key] = $value;
        return $this;
    }

    /**
     *
     * @param unknown_type $template
     * @param unknown_type $values
     * @param unknown_type $addslashes
     * @return string
     */
    protected function parse($template, $values, $addslashes)
    {
        preg_match_all('/\{\{(.*?)\}\}/si', $template, $res);
        if (@$res[1]) {
            foreach ($res[1] as $el)
            {
                if (isset($values[$el]))
                    $template = str_ireplace('{{' . $el . '}}', ($addslashes ? addslashes(stripslashes(@$values[$el])) : @$values[$el]), $template);
            }
        }
        return $template;
    }

    /**
     * @return string
     */
    public function render()
    {
        $template = $this->parse($this->_template, $this->_values, false);
        return $template;
    }


}

?>