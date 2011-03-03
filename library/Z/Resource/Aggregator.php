<?php

class Z_Resource_Aggregator
{
	protected static $_instance=NULL;
	
	/**
	 * 
	 * Enter description here ...
	 * @var Zend_Config_Ini
	 */
	protected $config;
	
	protected $models = array();
	
	protected function __construct()
	{
		$this->config = Zend_Registry::get('config');
		
		$res = $this->config->get('site');
		if (!$res) return;
		$res = $res->get('resource');
		if (!$res) return;
		$res = $res->get('aggregator');
		if (!$res) return;
		
		$this->config = $res;
		
		foreach ($this->config as $item)
		{
			$modelClass = $item->model;
			if (class_exists($modelClass) && method_exists($modelClass, 'ZGetLinks'))
			{
				$this->models[$modelClass] = new $modelClass();
			}
		}
		
	}
	
	/**
	 * 
	 * @return Z_Resource_Aggregator
	 */
	public static function getInstance()
	{
		if (NULL === self::$_instance)
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function getList($maxCount=0)
	{
		$return = array();
		foreach ($this->config as $item)
		{
			$modelClass = $item->model;
			$modelTitle = $item->title;
			if ($modelItems = $this->models[$modelClass]->ZGetLinks($maxCount))
			{
				$return[$modelTitle] = $modelItems;
			}
		}
		
		return $return;
	}
	
	
}