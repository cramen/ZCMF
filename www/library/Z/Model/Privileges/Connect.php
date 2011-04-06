<?php
require_once 'Z/Db/Table.php';
class Z_Model_Privileges_Connect extends Z_Db_Table
{
    protected $_name = 'z_privileges_connect';

    protected $_referenceMap = array(
        'Rule'  =>  array(
            'columns'           => 'rule_id',
            'refTableClass'     => 'Z_Model_Rules',
            'refColumns'        => 'id',
            'onDelete'          =>  self::CASCADE,
            'onUpdate'          =>  self::CASCADE,
        ),
    );

}