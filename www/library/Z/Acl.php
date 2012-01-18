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


class Z_Acl extends Zend_Acl
{
    protected static $_instance = null;

    public function __construct()
    {
        $resourcesModel = new Z_Model_Resources();
        $resources = $resourcesModel->fetchAll()->toArray();
        
        $resourceById = array();
        foreach ($resources as $resource)
        	$resourceById[$resource['id']] = $resource['resourceId'];

        $res_added = false;
		while(!$res_added)
		{
	        $res_added = true;
	        foreach($resources as $resource)
	        {
	        	$parentResourceId = array_key_exists($resource['parentid'],$resourceById)?$resourceById[$resource['parentid']]:NULL;
	            if ($parentResourceId==NULL || $this->has($parentResourceId))
	            {
	            	if (!$this->has($resource['resourceId']))
	            		$this->addResource($resource['resourceId'], $parentResourceId);
	            }
	            else
	            {
	            	$res_added = false;
	            }
	        }
		}

        $rolesModel = new Z_Model_Roles();
        $roles = $rolesModel->fetchAll()->toArray();// !!!
        foreach($roles as $role)
        {
            $this->addRoleParents($role);// !!!
            if(!$this->_getRoleRegistry()->has($role['roleId']))
            {
                $this->addRole($role['roleId'], $rolesModel->getParentsArray($role['id']));
            }
        }

        $privilegesModel = new Z_Model_Privileges();
        $rulesModel = new Z_Model_Rules();
        $rules = $rulesModel->getAllRules();
        foreach($rules as $rule)
        {
            if(empty($rule['roleId']))
            {
                $rule['roleId'] = null;
            }
            if(empty($rule['resourceId']))
            {
            	$rule['resourceId'] = null;
            }

            $privileges = $privilegesModel->getRulePrivileges($rule['id']);
            foreach ($privileges as $privilege)
            {
	            if(empty($privilege))
			    {
				    $privilege = null;
			    }
			    if('allow' === $rule['rule'])
	            {
	                $this->allow($rule['roleId'], $rule['resourceId'], $privilege);
	            }
	            elseif('deny' === $rule['rule'])
	            {
	                $this->deny($rule['roleId'], $rule['resourceId'], $privilege);
	            }
	            else
	            {
	                require_once 'Zend/Acl/Exception.php';
	                throw new Zend_Acl_Exception("Unsupported rule type; must be either '"
	                . self::TYPE_ALLOW . "' or '"
	                . self::TYPE_DENY . "'");
	            }
            }
        }
    }

    /**
     * 
     * @param $role
     */
    public function addRoleParents($role)
    {
        $rolesModel = new Z_Model_Roles();
        $parents = $rolesModel->getParents($role['id']);
        foreach($parents as $parent)
        {
            $this->addRoleParents($parent);
            if(!$this->_getRoleRegistry()->has($parent['roleId']))
            {
                $this->addRole($parent['roleId'], $rolesModel->getParentsArray($parent['id']));
            }
        }
    }

    /**
     * @return Z_Acl
     */
    public static function getInstance()
    {
        if(null === self::$_instance)
        {
	    	$cache = Z_Cache::getInstance();
	    	if (!$acl = $cache->load('z_acl'))
	    	{
	    		$acl = new Z_Acl();
	    		$cache->save($acl,'z_acl');
	    	}
            self::$_instance = $acl;
        }

        return self::$_instance;
    }
}