<?php

require_once 'Z/Db/Table.php';

class Z_Model_Rules extends Z_Db_Table
{
    protected $_name = 'z_rules';

    /**
     * @return array
     */
    public function getAllRules()
    {
        return $this->select()
            ->setIntegrityCheck(false)
            ->from($this->_name)
//            ->order('z_rules.orderid')
            ->join('z_resources', 'z_resources.id = ' . $this->_name . '.resource_id', array('resourceId'))
            ->join('z_roles', 'z_roles.id = ' . $this->_name . '.role_id', array('roleId'))
            ->query()
            ->fetchAll()
        ;
    }
}