<?php

class Z_Auth {

	private static $_instance = NULL;
	/**
	 * 
	 * @var Z_User
	 */
	private static $_userData = NULL;
	/**
	 * 
	 * @var Zend_Auth_Adapter_DbTable
	 */
	private static $_authAdapter = NULL;
	
	
	private function __construct()
	{
		$model = new Z_Model_Users();
		$tableName = $model->info('name');
		$dbAdapter = Zend_Db_Table::getDefaultAdapter();
		$authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
		$authAdapter->setTableName($tableName)
			->setIdentityColumn('login');
		self::$_authAdapter = $authAdapter;
	}
	
	/**
	 * @return Z_Auth
	 */
	public static function getInstance() {
		if (NULL === self::$_instance)
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	
	public function login($login='guest',$password='',$remember = false)
	{
		self::$_authAdapter->setIdentity($login)
			->setCredentialColumn('password')
			->setCredentialTreatment("CONCAT(MD5(CONCAT(?,SUBSTRING_INDEX(`password`,':',-1))),':',SUBSTRING_INDEX(`password`,':',-1))")
			->setCredential($password);
		$auth = Zend_Auth::getInstance();
		if ($auth->authenticate(self::$_authAdapter)->isValid())
		{
			if ($remember)
				Zend_Session::rememberMe(60*60*24*7);
			return true;
		}
		else
		{
			self::$_authAdapter->setIdentity('guest')->setCredential('');
			$auth->authenticate(self::$_authAdapter);
		}
		return false;
	}

	public function loginWithoutPassword($login)
	{
		self::$_authAdapter->setIdentity($login)
			->setCredentialColumn('login')
			->setCredentialTreatment('?')
			->setCredential($login);
		$auth = Zend_Auth::getInstance();
		if ($auth->authenticate(self::$_authAdapter)->isValid())
		{
			return true;
		}
		else
		{
			$this->login();
//			self::$_authAdapter->setIdentity('guest')->setCredential('');
//			$auth->authenticate(self::$_authAdapter);
		}
		return false;
	}
	
	/**
	 * @return Z_User
	 */
	public function getUser()
	{
		if (NULL === self::$_userData)
		{
			$auth = Zend_Auth::getInstance();
			if (!$auth->hasIdentity()) $this->login();
			try {
				$user = new Z_User($auth->getIdentity());
			}
			catch (Exception $e)
			{
				$this->login();
				$user = new Z_User($auth->getIdentity());
			}
			self::$_userData =  $user;
		}
		return self::$_userData;
	}

	public function logout()
	{
		$this->login();
	}
	
}
