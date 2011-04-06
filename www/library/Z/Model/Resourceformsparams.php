<?php

require_once 'Z/Db/Table.php';

class Z_Model_Resourceformsparams extends Z_Db_Table
{
    protected $_name = 'z_resources_forms_params';

    protected $_referenceMap = array(
        'Form'  =>  array(
            'columns'           => 'formid',
            'refTableClass'     => 'Z_Model_Resourceforms',
            'refColumns'        => 'id',
            'onDelete'          =>  self::CASCADE,
            'onUpdate'          =>  self::CASCADE,
        ),
    );    
}