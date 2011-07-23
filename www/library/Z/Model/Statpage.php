<?php

/**
 * News
 *  
 * @author cramen
 * @version 
 */

require_once 'Z/Db/Table.php';

class Z_Model_Statpage extends Z_Db_Table {
	/**
	 * The default table name 
	 */
	protected $_name = 'z_statpages';

	
	public function ZGetLinks($count=0)
	{
		$select = $this->select()
			->from($this,array('sid','title'))
			->order('title');
		if ($count) $select->limit($count);
		$result = $this->getAdapter()->fetchPairs($select);

		if (array_key_exists('index',$result))
		{
			$result = array_reverse($result);
			$result['/'] = $result['index'];
			unset($result['index']);
			$result = array_reverse($result);
		}
						
		return $result;
	}
}

