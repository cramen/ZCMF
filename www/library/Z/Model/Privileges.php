<?php

require_once 'Z/Db/Table.php';

class Z_Model_Privileges extends Z_Db_Table
{
	protected $_name = 'z_privileges';

	/**
	 * @return array
	 */
	public function getRulePrivileges($ruleKeyId)
	{
		$privileges = array();
		$privilegesFields = $this->select()
			->setIntegrityCheck(false)
		    ->from($this->_name . '_connect', array('id'))
		    ->join($this->_name, $this->_name . '.id = ' . $this->_name . '_connect.privilege_id', array('name'))
		    ->where('rule_id = ?', $ruleKeyId)
		    ->query()
		    ->fetchAll()
		;
		foreach($privilegesFields as $privilege)
		{
			$privileges[] = $privilege['name'];
		}

		return $privileges;
	}
}