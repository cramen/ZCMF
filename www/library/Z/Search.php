<?php

class Z_Search{

	protected static $_instance = NULL;
	
	protected function __construct()
	{		
	}
	
	/**
	 * 
	 * @return Zend_Search_Lucene_Interface
	 */
	public static function getInstance()
	{
		if (self::$_instance === NULL)
		{
			$indexDir = APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'lucene';
			Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive());
			Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
			try
			{
				$index = Zend_Search_Lucene::open($indexDir);
			}
			catch( Exception $e)
			{
				$index = Zend_Search_Lucene::create($indexDir);
			}
			self::$_instance = $index;
		}
		return self::$_instance;
	}
	
}
