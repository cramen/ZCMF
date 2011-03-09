<?php

class Admin_Acl_ResourcesController extends Z_Admin_Controller_Datacontrol_Abstract
{
	public function addSuccess($param)
	{
		if ($param['resourceId'] && $param['model'])
		{
			$nameExploded = explode('_',$param['resourceId']);
			$nameExploded = array_map('ucfirst',$nameExploded);
			
			$controllerName = $nameExploded[count($nameExploded)-1];
			unset($nameExploded[count($nameExploded)-1]);
			$ds = DIRECTORY_SEPARATOR;
			$path = APPLICATION_PATH.$ds.'modules'.$ds.$this->getRequest()->getModuleName().$ds.'controllers'.(($pathAdd=implode($ds,$nameExploded))?$ds.$pathAdd:'');
			$fileName = $controllerName.'Controller.php';
			$controllerName = ucfirst($this->getRequest()->getModuleName()).'_'.(($filePrefix=implode('_',$nameExploded))?$filePrefix.'_':'').$controllerName.'Controller';
			$class_file = new Zend_CodeGenerator_Php_Class(array(
							'name'			=>	$controllerName,
							'extendedclass'	=>	'Z_Admin_Controller_Datacontrol_Abstract',
						));
			Z_Fs::create_file($path.$ds.$fileName,"<?\n".$class_file->generate());
		}
	}
}
