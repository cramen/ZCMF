<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Model_Roles extends Z_Db_Table
{
    protected $_name = 'z_roles';

    /**
     * @return array
     */
    public function getParentsIds($roleKeyId)
    {
        $roleKeyId = (int) $roleKeyId;
        $parentsIds = array();
        $parentsFields = $this->select()
            ->setIntegrityCheck(false)
            ->from($this->_name . '_connect', array('parent_role_id'))
            ->where('child_role_id = ?', $roleKeyId)
            ->order('orderid')
            ->query()
            ->fetchAll()
        ;
        foreach($parentsFields as $parent)
        {
            $parentsIds[] = (int) $parent['parent_role_id'];
        }

        return $parentsIds;
    }

    /**
     * 
     * @param $roleKeyId
     * 
     * @return array
     */
    public function getParents($roleKeyId)
    {
        $roleKeyId = (int) $roleKeyId;

        $connect = new Z_Model_Roles_Connect();

        $parentsFields = $connect->getParents($roleKeyId);
//        $parentsIds = $this->getParentsIds($roleKeyId);
//        if(!empty($parentsIds))
//        {
//            $parentsFields = $this->select($this->_name . '_connect', array('parent_role_id', 'child_role_id', 'orderid'))
//                ->setIntegrityCheck(false)
//                ->join($this->_name, $this->_name . '.id = ' . $this->_name . '_connect.child_role_id', array('id', 'roleId'))
//                ->where('parent_role_id = ?', $roleKeyId)
////                ->where('id IN (' . implode(',', $parentsIds) . ')')// !!!!!!!!
//                ->order('orderid')
//                ->query()
//                ->fetchAll()
//            ;
////        }

        return $parentsFields;
    }

    /**
     * 
     * @param $roleKeyId
     * 
     * @return array
     */
    public function getParentsArray($roleKeyId)
    {
        $roleKeyId = (int) $roleKeyId;
        $parents = array();
        $parentsFields = $this->getParents($roleKeyId);

        foreach($parentsFields as $parent)
        {
            $parents[] = $parent['roleId'];
        }

        return $parents;
    }
}