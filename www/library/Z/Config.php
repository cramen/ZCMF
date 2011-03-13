<?php

class Z_Config {

	/**
	 * 
	 * @var Z_Db_Table
	 */
	protected static $_model = NULL;
	
	/**
	 * 
	 * @var Zend_Db_Table_Row
	 */
	protected $_row = NULL;
	
	public function __construct($sid)
	{
		$this->_row = $this->_getRow($sid);
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		if (is_object($this->_row))
			return $this->_row->value;
		else
			return '';
	}
	
	/**
	 * @return Zend_Db_Table_Row
	 */
	protected function _getRow($sid)
	{
		if (NULL === self::$_model)
			self::$_model = new Z_Model_Config();
		$row = self::$_model->fetchRow(array('sid=?'=>$sid));
		if (!$row)
		{
			return false;
		}
		return $row;
	}
	
	public function __toString()
	{
		return $this->getValue();
	}
	
	public function __toString()
	{
		return $this->getValue();
	}
	
}

?>