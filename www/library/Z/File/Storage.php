<?php

class Z_File_Storage
{
	protected $_storageDir;
	protected $_dbTable;
	protected $_model;
	protected static $_dirChars = 
array('a','b','c','d','e','f',
'g','h','i','j','k','l',
'm','n','o','p','r','s',
't','u','v','x','y','z',
'A','B','C','D','E','F',
'G','H','I','J','K','L',
'M','N','O','P','R','S',
'T','U','V','X','Y','Z',
'1','2','3','4','5','6',
'7','8','9','0');
	
	
	public function __construct($param = array())
	{
		$this->_storageDir = isset($param['storage']) ? $param['storage'] : SITE_PATH. '/' . 'storage';
	}

	/**
	 * Сохранить инфомацию о файле в базу
	 * @param Z_File_Storage_File $file
	 * @return bool
	 */
	public function save (Z_File_Storage_File $file)
	{
		$id = $file->get('id');
		if($id == null)
			$item = $this->getModel()->createRow($file->get());
		else
		{
			$item = $this->getModel()->fetchRow(array('id=?' => $id));
			$item->setFromArray($file->get());
		}
		return $item->save();
	}

	/**
	 * копирует файл в папку назначения и создает запись в БД
	 * @param string $localName Путь к файлу
	 * @param array() $param настройки: name
	 * realname - настоящее имя файла
	 * filename - новое имя хранимого файла. Если не указано, пытается сгенериться длиной в 8 знаков с 5ти попыток
	 * @return int id
	 */
	public function create($localName, $param=array())	//может, $localName засунуть в массив?
	{
		$folder = isset($param['foldername']) ? $param['foldername'] : $this->getNewFolderName();
		$name = Z_Transliterator::translateCyr($param['realname']);
		if(!$name || !$this->copyFileNewDir($localName, $folder, $name))
			return false;
		$auth = Z_Auth::getInstance();
		$file = new Z_File_Storage_File(array('user_id' => $auth->getUser()->getId(),
				'name' => $name,
				'realname' => $param['realname'],
				'path' => $folder,
				'fullpath' => $this->_storageDir . '/' . $folder));
		return $this->save($file);
	}

	/**
	 * Замена сохраненного в базе файла на загружаемый
	 * @param <type> $id
	 * @param <type> $data
	 * @return <type> 
	 */
	public function replace($id, $localName, $realname)
	{
		$file = $this->getFile($id);
		if($file == null)
			return false;
		if (is_file($file->getFullName()))
		{
			unlink($file->getFullName());
		}
		$auth = Z_Auth::getInstance();
		$name = Z_Transliterator::translateCyr($realname);
		$data = array('user_id' => $auth->getUser()->getId(),
				'name' => $name,
				'realname' => $realname);
		$file->set($data);
		$copy = $this->copyFileNewDir($localName, $file->get('path'), $name);
		$save = $this->save($file);
		return $copy && $save; //$this->copyFileNewDir($localName, $file->getPath(), $name) && $this->save($file);
	}

/**
 * Генерирует новое имя для файла. Првоеряет, может ли быть создана персональная директория под этот файл
 * @param int $length Длина имени создаваемого файла
 * @param int $searchDepth Количество попыток создания
 * @return при успехе возвращает доступное имя, при неудаче - false
 */
	public function getNewFolderName($length = 8, $searchDepth = 5)
	{
            for($j = 0; $j < $searchDepth; $j++)
            {
                $name = "";
                shuffle(self::$_dirChars);
                for($i = 0; $i < $length; $i++)
                        $name .= self::$_dirChars[$i];
                if(!is_dir($this->_storageDir . '/' . $name))
					return $name;
            }
            return false;
	}

	/**
	 * Получить полный путь к папке хранилища
	 * @return string 
	 */
	public function getStorageDir()
	{
		return $this->_storageDir;
	}

	/**
	 * Получение файла из базы по id
	 * @param int $id
	 * @return Z_File_Storage_File
	 */
	public function getFile($id)
	{
        $result = $this->getModel()->find($id);
        if (0 == count($result))
            return null;
		$data = $result->current()->toArray();
		$data['fullpath'] = $this->_storageDir . '/' .  $data['path'];
        return new Z_File_Storage_File($data);
	}

	/**
	 * Копирует файл в новое место с cозданием персональной папки для файла
	 * @param string $src Путь к файлу-источнику
	 * @param string $destFolder Папка назначения
	 * @param string $destName Имя конечного файла
	 * @return bool
	 */
	protected function copyFileNewDir($src, $destFolder, $destName)
	{
		$dir = $this->_storageDir . '/' .  $destFolder;
		$dirCreated = is_dir($dir) ? true : mkdir($dir);
		return $dirCreated && copy($src, $dir . '/' . $destName);
	}

	/**
	 * Удаление по id файла его самого и каталога, в котором он хранится
	 * @param int $id идентификатор файла в базе
	 * @return bool
	 */
	public function removeFileDir($id)
	{
		$file = $this->getFile((int) $id);
		if(!$file || !$this->getModel()->delete(array('id = ?'=>$id)))
			return false;
		$this->_rmdir($file->get('fullpath'));
		return true;
	}

/**
 * получить модель. Если ее нет - создает новую
 * @return Z_Model_Upload
 */
	public function getModel()
	{
		if(!$this->_model)
			$this->_model = new Z_Model_Upload();
		return $this->_model;
	}

	/**
	 * Рекурсивная очистка папки
	 * @param string $dir путь к папке
	 */
	protected function _rmdir($dir)
	{
		$files = glob($dir.'*',GLOB_MARK);
		foreach($files as $file)
		{
			if(is_dir($file))
				$this->_rmdir($file);
			else
				unlink($file);
		}
		if (is_dir($dir)) rmdir($dir);
	}
}
