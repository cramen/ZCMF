<?php

class Z_Log
{

	protected static $_instance = NULL;
	
	protected function __construct()
	{
		self::$_instance =  new Zend_Log();
		if (Z_Db_Table::getDefaultAdapter())
		{
//			$logModel = new Z_Model_Log();
//			$logTable = $logModel->info('name');
			$writer = new Zend_Log_Writer_Db(Z_Db_Table::getDefaultAdapter(),'z_log');
		}
		else
		{
			$writer = new Zend_Log_Writer_Stream(APPLICATION_PATH.DIRECTORY_SEPARATOR.'data/info.log');
		}
		self::$_instance->addWriter($writer);
	}
	
	/**
	 * @return Zend_Log
	 */
	public static function getInstance()
	{
		if (NULL===self::$_instance)
		{
			new self(); 
		}
		return self::$_instance;
	}
}

?>