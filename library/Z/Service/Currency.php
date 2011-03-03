<?php

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