<?php
require_once 'Z/Db/Table.php';
class Z_Model_Roles_Connect extends Z_Db_Table
{
    protected $_name = 'z_roles_connect';

    /**
     *
     * @param $roleKeyId
     *
     * @return array
     */
    public function getParents($roleKeyId)
    {
        $roleKeyId = (int) $roleKeyId;

        $parentsFields = $this->select($this->_name)
            ->setIntegrityCheck(false)
            ->join('z_roles', 'z_roles.id = ' . $this->_name . '.parent_role_id', array('id', 'roleId'))
            ->where('child_role_id = ?', $roleKeyId)
            ->order('orderid')
            ->query()
            ->fetchAll()
        ;

        return $parentsFields;
    }
}