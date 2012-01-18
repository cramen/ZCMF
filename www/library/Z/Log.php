<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Лицензия
 *
 * Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации
 * (в дальнейшем именуемыми "Программное Обеспечение"),безвозмездно использовать Программное Обеспечение без ограничений,
 * включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам,
 * которым предоставляется данное Программное Обеспечение, соблюдении следующих условий:
 *
 * Вышеупомянутый копирайт и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
 *
 * При копировании, добавлении, изменении, распространении, продаже, публикации и сублицензировании программного обеспечения,
 * авторство может быть только дополнено, но не удалено или изменено на другое.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ,
 * ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И
 * ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА,
 * УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ
 * ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 *
 */

class Z_Log
{

	protected static $_instance = NULL;
	
	protected function __construct()
	{
		self::$_instance =  new Zend_Log();
		if (Z_Db_Table::getDefaultAdapter())
		{
			$logModel = new Z_Model_Log();
			$writer = new Zend_Log_Writer_Db(Z_Db_Table::getDefaultAdapter(),$logModel->info('name'));
		}
		else
		{
			$writer = new Zend_Log_Writer_Stream(APPLICATION_PATH.'/data/info.log');
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

    public static function log($message, $priority, $extras = null)
    {
        self::getInstance()->log($message, $priority, $extras);
    }

    public static function alert($message, $extras = null)
    {
        self::log($message, Zend_Log::ALERT, $extras);
    }

    public static function critical($message, $extras = null)
    {
        self::log($message, Zend_Log::CRIT, $extras);
    }

    public static function error($message, $extras = null)
    {
        self::log($message, Zend_Log::ERR, $extras);
    }

    public static function warning($message, $extras = null)
    {
        self::log($message, Zend_Log::WARN, $extras);
    }

    public static function notice($message, $extras = null)
    {
        self::log($message, Zend_Log::NOTICE, $extras);
    }

    public static function info($message, $extras = null)
    {
        self::log($message, Zend_Log::INFO, $extras);
    }

    public static function debug($message, $extras = null)
    {
        self::log($message, Zend_Log::DEBUG, $extras);
    }

}
