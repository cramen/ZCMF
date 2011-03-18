<?php

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