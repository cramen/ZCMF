<?php

require_once 'Z/Db/Table.php';

class Z_Model_Resourcebuttons extends Z_Db_Table
{
    protected $_name = 'z_resources_buttons';

    protected $_referenceMap = array(
        'Resource'  =>  array(
            'columns'           => 'resourceid',
            'refTableClass'     => 'Z_Model_Resources',
            'refColumns'        => 'id',
            'onDelete'          =>  self::CASCADE,
            'onUpdate'          =>  self::CASCADE,
        ),
    );
}