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

require_once 'Zend/Db/Table/Abstract.php';

class Z_Db_Table extends Zend_Db_Table_Abstract
{

    /**
     * Возвращает ассоцитиативный массив, где ключем является поле, указанное в первом элементе параметра $keys а значение во второом
     * @param <array('id','title')> $keys
     * @param <array> $where
     * @param <array or string> $order
     * @return <array>
     */
    public function fetchPairs($keys = NULL, $where = NULL, $order = NULL)
    {
        if ($keys === NULL) $keys = array('id', 'title');
        if ($where === NULL) $where = array();
        $select = $this->select()->from($this, $keys);
        if (!empty($where)) {
            foreach ($where as $key => $value)
            {
                $select->where($key, $value);
            }
        }
        if (!empty($order)) $select->order($order);
        $ret = $this->getAdapter()->fetchPairs($select);
        return $ret;
    }

    /**
     * Возвращяет элементы с заданными id из входного массива в порядке следования их во входном массиве
     * @param <array> $ids
     * @return Zend_Db_Table_Rowset
     */
    public function fetchByIds($ids = array(), $where = array(), $order = NULL, $count = NULL, $offset = NULL)
    {
        return $this->fetchAll($this->fetchByIdsSelect($ids, $where, $order, $count, $offset));
    }

    /**
     *
     * @return Zend_Db_Table_Select
     */
    public function fetchByIdsSelect($ids = array(), $where = array(), $order = NULL, $count = NULL, $offset = NULL)
    {
        if (!$where) $where = array();
        $id_list = implode(',', $ids);
        if (!empty($ids)) {
            $where['goods.id IN (' . $id_list . ')'] = '';
            $order = $order ? $order : 'FIELD(' . $this->info('name') . '.id, ' . $id_list . ')';
        }
        else
        {
            $where['false'] = '';
        }
        $select = $this->select(true);
        foreach ($where as $key => $where_item)
            $select->where($key, $where_item);
        $select->order($order);
        $select->limit($count, $offset);
        return $select;
    }

    /**
     * Генерирует уникальный идентификатор sid на основе строки $str
     * @param strong $str
     * @param string $field
     * @param int $iter
     * @return string
     */
    public function generateSid($str,$field = 'sid',$iter = 1)
    {
        $sid = Z_Transliterator::translateCyr($str);
        $sid = str_replace(array(' ','<','>','#','%','"','\'','{','}','|','\\','^','[',']','`',';','?',':','@','&','=','+','$',',','—'),'_',$sid);
        $sid .= ($iter==1?"":"_".$iter);
        $sid = preg_replace('~_+~','_',$sid);
        $sid = trim($sid,'_');
        if ($this->fetchRow(array($field.'=?'=>$sid)))
        {
            $sid = $this->generateSid($str,$field,$iter+1);
        }
        return $sid;
    }

}
