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
class Z_File_Storage_File
{
	protected $_data = array();
	protected $_model;

	/**
	 * @param array
	 */
	public function __construct($data = array())
	{
		$this->set($data);
	}
	
	/**
	 * Установка параметров файла
	 * @param array | string $key
	 * @param string $value
	 * @return Z_File_Storage_File
	 */
	public function set($key, $value = null)
	{
		if(is_array($key))
			$this->_setData($key);
		else
		{
			if ($value != NULL)
				$this->data[$key] = $value;
			else
				unset($this->data[$key]);
		}
		return $this;
	}
	
	/**
	 * Получение параметра файла по ключу
	 * @param string $key
	 * @return array | string
	 */
	public function get($key=NULL)
	{
		if ($key)
		{
			if (isset($this->_data[$key]))
				return $this->_data[$key];
			else
				return NULL;
		}
		else
		{
			return $this->_data;
		}
	}
	
	/**
	 * Получить id пользователя, сохранившего файл
	 * @return mixed (int | null)
	 */
	public function getUserId()
	{
		return $this->get('user_id');
	}
	
	/**
	 * Получить исходное имя файла
	 * @return string | null
	 */
	public function getRealName()
	{
		return $this->get('realname');
	}
	
	/**
	 * Получить имя файла
	 * @return string | null
	 */
	public function getName()
	{
		return $this->get('name');
	}

	/**
	 * Получить имя файла от корня файловой системы
	 * @return string | null
	 */
	public function getFullName()
	{
		return $this->getPath() . '/' . $this->get('name');
	}
	
	/**
	 * Возвращает имя файла от корня сайта
	 * @return string
	 */
	public function getSiteName()
	{
		return $this->getSitePath() . '/' . $this->getName();
	}
	
	/**
	 * Возвращает путь к файлу (от корня файловой системы)
	 * @return string
	 */
	public function getPath()
	{
		return $this->get('fullpath');
	}
	
	/**
	 * Возвращает путь к файлу (от корня сайта)
	 * @return string
	 */
	public function getSitePath()
	{
		return str_replace(SITE_PATH, "", $this->getPath());
	}

	
	public function  __toString()
	{
		return $this->getSiteName();
	}
	
	/**
	 * Сохранить информацию о файле в базу
	 * @param Z_File_Storage $storage
	 * @return bool
	 */
	public function save(Z_File_Storage $storage)
	{
		return $storage->save($this);
	}
	
	/**
	 * установка массива значений
	 * @param array $data
	 */
	protected function _setData($data)
	{
		if (is_array($data))
		{
			foreach ($data as $key=>$el)
			{
				$this->_data[$key] = $el;
			}
		}
	}
}