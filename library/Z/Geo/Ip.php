<?php

/**
 * Класс для работы с базой географической привязки блоков ip адресов
 * @author cramen
 *
 */
class Z_Geo_Ip {
	
	protected $namespace = NULL;
	
	protected $ip = NULL;
	
	protected $block = NULL;
	protected $city = NULL;
	protected $area = NULL;
	
	public function __construct($ip=NULL,$clearCache=false)
	{
		if (NULL===$ip) $ip = $_SERVER['REMOTE_ADDR'];
		if (!is_numeric($ip))
		{
			$ip = explode('.',$ip);
			$ip = ($ip[0]*256*256*256)+($ip[1]*256*256)+($ip[2]*256)+($ip[3]);
		}
		$this->ip = $ip;
		if ($clearCache)
		{
			$this->clearCache();
		}
	}

	/**
	 * @return Zend_Session_Namespace
	 */
	protected function _getNameSpace()
	{
		if (NULL===$this->namespace)
		{
			$this->namespace = new Zend_Session_Namespace('geoiplocation',true);
		}
		return $this->namespace;
	}

	/**
	 * @return Zend_Db_Table_Row
	 */
	protected function _getBlock() {
		if (NULL===$this->block)
		{
			$modelBlock = new Z_Model_Geo_Blocks();
			$this->block = $modelBlock->fetchRow(array(
				'start<=?'		=>	$this->ip,
				'stop>=?'		=>	$this->ip,
			));
			if (!$this->block)
				$this->block = $modelBlock->fetchRow();
		}
		return $this->block;
	}
	
	/**
	 * @return Zend_Db_Table_Row
	 */
	protected function _getCity() {
		if (NULL===$this->city)
		{
			$modelCity = new Z_Model_Geo_Cityes();
			$ns = $this->_getNameSpace();
			if ($ns->cityId)
			{
				$this->city = $modelCity->find($ns->cityId)->current();
			}
			else
			{
				$this->city = $modelCity->find($this->_getBlock()->z_geo_cityes_id)->current();
				$ns->cityId = $this->city->id;
			}
		}
		return $this->city;
	}

	/**
	 * @return Zend_Db_Table_Row
	 */
	protected function _getArea() {
		if (NULL===$this->area)
		{
			$modelArea = new Z_Model_Geo_Areas();
			$this->area = $modelArea->find($this->_getCity()->z_geo_areas_id)->current();
		}
		return $this->area;
	}
	
	
	
	public function getCity()
	{
		return $this->_getCity()->city;
	}

	public function getCityId()
	{
		return $this->_getCity()->id;
	}

	public function getArea()
	{
		return $this->_getArea()->area;
	}

	public function getAreaId()
	{
		return $this->_getArea()->id;
	}
	
	public function setCity($id)
	{
		$modelCity = new Z_Model_Geo_Cityes();
		$city = $modelCity->find($id)->current();
		if ($city)
		{
			$ns = $this->_getNameSpace();
			$this->city = $city;
			$ns->cityId = $city->id;
		}
		else
		{
			return false;
		}
	}
	
	public function clearCache()
	{
		$ns = $this->_getNameSpace();
		$ns->cityId = NULL;
	}

	public static function import()
	{
	  set_time_limit(0);
	  //-------------------Парсинг файла с блоками адресов и заполнение БД-------------------//

	  $geoPath = APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'geoip';
	  $downloadFileName = $geoPath.DIRECTORY_SEPARATOR.'base.tar.gz';
	  $fileName = $geoPath.DIRECTORY_SEPARATOR.'cidr_ru_block.txt';
	  $infoFileName = $geoPath.DIRECTORY_SEPARATOR.'info.txt';
	  if (file_exists($infoFileName)) return;

	  file_put_contents($infoFileName, 'delete');
	  //удаление старых файлов
//	  if (file_exists($downloadFileName))
//	    unlink($downloadFileName);
//	  if (file_exists($fileName))
//	    unlink($fileName);

	  file_put_contents($infoFileName, 'download');
	  //скачиваем новую базу
//	  $downloadFileContent = file_get_contents('http://ipgeobase.ru/files/db/Main/db_files.tar.gz');
//	  file_put_contents($downloadFileName, $downloadFileContent);

	  //распаковка файлов
	  $extractor = new Z_Archive_Extractor();
	  $extractor->extractArchive($downloadFileName,$geoPath);

	  define('IP_BLOCK_START',0);
	  define('IP_BLOCK_STOP',1);
	  define('IP_BLOCK_CITY',4);
	  define('IP_BLOCK_AREA',5);
	  define('IP_BLOCK_DISTRICT',6);

	  $blocksModel	=	new Z_Model_Geo_Blocks();
	  $cityesModel	=	new Z_Model_Geo_Cityes();
	  $areasModel		=	new Z_Model_Geo_Areas();
	  $districtsModel	=	new Z_Model_Geo_Districts();

	  $districts = $districtsModel->getAdapter()->fetchPairs($districtsModel->select(true)
		  ->reset(Zend_Db_Select::COLUMNS)
		  ->columns(array('district','id')));

	  $areas = $areasModel->getAdapter()->fetchPairs($areasModel->select(true)
		  ->reset(Zend_Db_Select::COLUMNS)
		  ->columns(array('area','id')));

	  $cityes = $cityesModel->getAdapter()->fetchPairs($cityesModel->select(true)
		  ->reset(Zend_Db_Select::COLUMNS)
		  ->columns(array('city','id')));

	  $blocksModel->getAdapter()->query('TRUNCATE TABLE  `'.$blocksModel->info('name').'`');


	  if (($handle = fopen($fileName,'r')) !== false)
	  {
	    $lastBlockStart	= 0;
	    $lastBlockStop	= 0;
	    $lastBlockCity	= '';

	    $i=0;
	    while (($data = Z_Csv::fgetcsv($handle, 1000, "\t")) !== FALSE)
	    {
	      $data[IP_BLOCK_AREA]      = iconv('WINDOWS-1251','UTF-8',$data[IP_BLOCK_AREA]);
	      $data[IP_BLOCK_CITY]      = iconv('WINDOWS-1251','UTF-8',$data[IP_BLOCK_CITY]);
	      $data[IP_BLOCK_DISTRICT]  = iconv('WINDOWS-1251','UTF-8',$data[IP_BLOCK_DISTRICT]);
	      $data[IP_BLOCK_START]     =	(int)$data[IP_BLOCK_START];
	      $data[IP_BLOCK_STOP]     =	(int)$data[IP_BLOCK_STOP];

	      //добавление округа
	      if (!array_key_exists($data[IP_BLOCK_DISTRICT],$districts))
	      {
		$districtRow = $districtsModel->createRow(array(
			'district'	=>	$data[IP_BLOCK_DISTRICT]
		));
		$districtRow->save();
		$districts[$districtRow->district] = $districtRow->id;
	      }
	      //добавление области
	      if (!array_key_exists($data[IP_BLOCK_AREA],$areas))
	      {
		$areaRow = $areasModel->createRow(array(
			'z_geo_districts_id'	=>	$districts[$data[IP_BLOCK_DISTRICT]],
			'area'					=>	$data[IP_BLOCK_AREA]
		));
		$areaRow->save();
		$areas[$areaRow->area] = $areaRow->id;
	      }
	      //добавление города
	      if (!array_key_exists($data[IP_BLOCK_CITY],$cityes))
	      {
		$cityRow = $cityesModel->createRow(array(
			'z_geo_areas_id'	=>	$areas[$data[IP_BLOCK_AREA]],
			'city'					=>	$data[IP_BLOCK_CITY]
		));
		$cityRow->save();
		$cityes[$cityRow->city] = $cityRow->id;
	      }
	      //добавление блока
	      if ($lastBlockStart <= $data[IP_BLOCK_START] && $lastBlockStop >= $data[IP_BLOCK_STOP])
	      {
		//если блок внутри существующего, но с другим городом, то добавляем, иначе ничего не делаем
		if ($lastBlockCity != $data[IP_BLOCK_CITY])
		{
		  $blocksModel->insert(array(
			  'z_geo_cityes_id'	=>	$cityes[$data[IP_BLOCK_CITY]],
			  'start'					=>	$data[IP_BLOCK_START],
			  'stop'					=>	$data[IP_BLOCK_STOP]
		  ));
		  $i++;
		  if ($i%1000 == 0)
		  {
		    file_put_contents($infoFileName, $i);
		    sleep(2);
		  }
		}
	      }
	      else
	      {
		//если этот блок не является подблоком, то добавляем
		$blocksModel->insert(array(
			'z_geo_cityes_id'	=>	$cityes[$data[IP_BLOCK_CITY]],
			'start'					=>	$data[IP_BLOCK_START],
			'stop'					=>	$data[IP_BLOCK_STOP]
		));
		$i++;
		if ($i%1000 == 0)
		{
		  file_put_contents($infoFileName, $i);
		  sleep(2);
		}

		$lastBlockStart = $data[IP_BLOCK_START];
		$lastBlockStop  = $data[IP_BLOCK_STOP];
		$lastBlockCity  = $data[IP_BLOCK_CITY];
	      }


	    }
	  }

	  unlink($infoFileName);

	}

	public static function status()
	{
	  $geoPath = APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'geoip';
	  $infoFileName = $geoPath.DIRECTORY_SEPARATOR.'info.txt';
	  if (file_exists($infoFileName))
	  {
	    return file_get_contents($infoFileName);
	  }
	  else
	  {
	    return false;
	  }
	}
	
}
