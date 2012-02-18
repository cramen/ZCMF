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

require_once 'Z/Db/Table.php';

class Z_Model_Resources extends Z_Db_Table
{
    protected $_name = 'z_resources';
    protected static $_resources = NULL;
    protected $_dependentTables = array(
        'Z_Model_Resourcebuttons',
        'Z_Model_Resourcecolumns',
        'Z_Model_Resourceconditions',
        'Z_Model_Resourceforms',
        'Z_Model_Resourcejoins',
        'Z_Model_Resourcerefers',
        'Z_Model_Rules',
        'Z_Model_Resources',
    );

    protected $_referenceMap = array(
        'Resource' => array(
            'columns' => 'parentid',
            'refTableClass' => 'Z_Model_Resources',
            'refColumns' => 'id',
            'onDelete' => self::CASCADE,
            'onUpdate' => self::CASCADE,
        ),
    );


    /**
     * @return string
     */
//    public function getResourceId($id)
//    {
//    	if (NULL===self::$_resources)
//    	{
//    		$select = $this->select(true)->reset(Zend_Db_Select::COLUMNS)->columns(array('id','resourceId'));
//    		self::$_resources = $this->getAdapter()->fetchPairs($select);
//    	}
//    	
//        $id = (int) $id;
//
//        if (array_key_exists($id,self::$_resources))
//        {
//            return self::$_resources[$id];
//        }
//        else
//        {
//            return null;
//        }
//    }

    public function fetchPairsCat($parentid = 0, $level = 0)
    {
        $where = array();
        $where['parentid=?'] = $parentid;
        $items = $this->fetchPairs(array('id', 'title'), $where, 'orderid');
        $retitems = array();
        $pref = str_repeat('--', $level);
        //    	$level++;
        foreach ($items as $key => $value)
        {
            $retitems[$key] = $pref . $value;
            if ($additems = $this->fetchPairsCat($key, $level + 1)) {
                $retitems += $additems;
            }
        }
        return $retitems;
    }
}