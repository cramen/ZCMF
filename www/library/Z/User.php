<?php


class Z_User
{

	private $data = array();
	protected static $_saltChars = array('a','b','c','d','e','f',
'g','h','i','j','k','l',
'm','n','o','p','r','s',
't','u','v','x','y','z',
'A','B','C','D','E','F',
'G','H','I','J','K','L',
'M','N','O','P','R','S',
'T','U','V','X','Y','Z',
'1','2','3','4','5','6',
'7','8','9','0');
	
	public function __construct ($value = "", $field = "login")
	{
		if ($value) if (! $this->_getByField($field, $value)) throw new Exception(
		'Пользователь не найден');
	}

	protected function _getByField ($field, $value)
	{
		$modelUser = new Z_Model_Users();
		$data = $modelUser->fetchAll(array(
			$field . '=?' => $value
		));
		if ($data->count() > 1 || $data->count() == 0)
		{
			return false;
		}
		else
		{
			$this->data = $data->current()
				->toArray();
		}
		return true;
	}

	protected static function _genSalt ()
	{
		shuffle(self::$_saltChars);
		return substr(implode('',self::$_saltChars),0,32);
	}
	
	public static function _cryptPassword($password)
	{
		$salt =  self::_genSalt();
		return md5($password.$salt).':'.$salt;
	}

	public function get ($key = NULL)
	{
		if ($key)
		{
			if (isset($this->data[$key])) return $this->data[$key];
			else return NULL;
		}
		else
		{
			return $this->data;
		}
	}

	public function getLogin ()
	{
		return $this->get('login');
	}

	public function getId ()
	{
		return $this->get('id');
	}

	public function getRole ()
	{
		$rolesModel = new Z_Model_Roles();
		$roleid = $this->get('role_id');
		$role = $rolesModel->fetchRow(array(
			'id=?' => $roleid
		));
		if ($role) return $role->roleId;
		return 'deny';
	}

	/**
	 *
	 * @return Z_User
	 */
	public function set ($key, $value = NULL)
	{
		if (is_array($key))
		{
			$this->_setParams($key);
		}
		else
		{
			if ($value !== NULL) $this->data[$key] = $value;
			else unset($this->data[$key]);
		}
		return $this;
	}

	protected function _setParams ($data)
	{
		if (is_array($data))
		{
			foreach ($data as $key => $el)
			{
				$this->data[$key] = $el;
			}
		}
	}

	/**
	 * Сохраняет пользователя
	 * @return int
	 */
	public function save ()
	{
		$modelUser = new Z_Model_Users();
		$user = NULL;
		if (isset($this->data['id']) && is_numeric($this->data['id']))
		{
			$user = $modelUser->find($this->data['id'])->current();
		}
		if ($user)
		{
			if ($this->data['password'] != $user->password)
			{
				$this->data['password'] = self::_cryptPassword($this->data['password']);
			}
			
			return $user->setFromArray($this->data)
				->save();
		}
		return false;
	}

	/**
	 * удаляет пользователя
	 * @return int
	 */
	public function delete ()
	{
		$modelUser = new Z_Model_Users();
		if (isset($this->data['id']) && is_numeric($this->data['id']))
		{
			$user = $modelUser->find($this->data['id'])
				->current();
		}
		if (isset($user) && $user)
		{
			return $user->delete();
		}
		else
		{
			return false;
		}
	}

	/**
	 * Создает пользователя
	 */
	public static function create ($login, $password, $params = array())
	{
		$modelUsers = new Z_Model_Users();
		$modelRoles = new Z_Model_Roles();
		if ($modelUsers->fetchRow(array(
			'login=?' => $login
		))) return false;
		$params['login'] = $login;
		$params['password'] = self::_cryptPassword($password);
		if (! isset($params['role_id'])) $params['role_id'] = $modelRoles->fetchRow(
		array(
			'roleId=?' => 'guest'
		))->id;
		$userRow = $modelUsers->createRow($params);
		$userRow->save();
		return new Z_User($userRow->login);
	}
}

