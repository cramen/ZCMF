<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

require_once 'phpQuery.php';

class Z_Service_Currency
{
	protected $currency=array();
	
	protected $url = 'http://www.cbr.ru/scripts/XML_daily.asp';
	
	public function __construct()
	{
		$cache = Z_Cache::getInstance();
		
		if (!$this->currency = $cache->load('z_currency_data'))
		{
			$this->currency = $this->_getCurrencyArray();
			$cache->save($this->currency,'z_currency_data','',86400);
		}
		
	}
	
	public function get_currency($var=NULL)
	{
		if (is_string($var) and array_key_exists(strtoupper($var), $this->currency))
		{
			return $this->currency[strtoupper($var)];
		}
		elseif (is_array($var))
		{
			$res = array();
			foreach ($var as $key)
			{
				$key = strtoupper($key);
				$res[$key] = $this->get_currency($key);
			}
			return $res;
		}
		return $this->currency;
	}	
	
	protected function _getCurrencyArray()
	{
		$client = new Zend_Http_Client($this->url);
		$response = $client->request('GET');
		
		if ($response->getStatus() != 200) throw new Zend_Exception('Не удалось получить страницу курсов валют');
		
		$body = $response->getBody();

		$phpq = phpQuery::newDocumentXML($body);
		
		$items = $phpq->find('ValCurs Valute');
		
		$result = array();
		foreach ($items as $el)
		{
			$code = pq($el)->find('CharCode')->text();
			$result[$code] = array(
				'name'		=>	pq($el)->find('Name')->text(),
				'value'		=>	pq($el)->find('Value')->text(),
				'nominal'	=>	pq($el)->find('Nominal')->text()
			);
		}
		
		return $result;
		
	}
	
}