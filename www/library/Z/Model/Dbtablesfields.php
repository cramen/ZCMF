<?php

/**
 * News
 *  
 * @author cramen
 * @version 
 */

require_once 'Z/Db/Table.php';

class Z_Model_Dbtablesfields extends Z_Db_Table {
	/**
	 * The default table name 
	 */
	protected $_name = 'z_dbtables_fields';

    protected $_referenceMap = array(
        'Table'  =>  array(
            'columns'           => 'dbtable_id',
            'refTableClass'     => 'Z_Model_Dbtables',
            'refColumns'        => 'id',
            'onDelete'          =>  self::CASCADE,
            'onUpdate'          =>  self::CASCADE,
        ),
    );

}

