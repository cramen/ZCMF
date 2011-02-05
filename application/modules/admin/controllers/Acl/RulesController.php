<?php

class Admin_Acl_RulesController extends Z_Admin_Controller_Datacontrol_Abstract
{
	
//	public function init()
//	{
//		$this->z_title = 'Правила';
//		$this->z_model = new Z_Model_Rules();
//		$this->z_parent_controller = array(
//			'controller'	=>	'acl_roles',
//			'field'			=>	'role_id'
//		);
//		$this->z_columns = array(
//			'title'		=>	array(
//				'title'		=>	'Ресурс',
//			),
//			'rule'		=>	array(
//				'title'		=>	'Правило',
//				'eval'		=>	'return "{{rule}}"=="allow"?"Разрешено":"Запрещено";',
//			),
//		);
//		
//		$resourcesModel = new Z_Model_Resources();
//		$resourcesTable = $resourcesModel->info('name');
//		
//		$this->z_joins = array(
//			array(
//				'table'		=>	$resourcesTable,
//				'condition'	=>	$this->z_model->info('name').'.resource_id='.$resourcesTable.'.id',
//				'columns'	=>	array('title')
//			),
//		);
//		
//		$this->z_refer = array(
//			'privileges'	=>	array(
//				'model'		=>	new Z_Model_Privileges_Connect(),
//				'field1'	=>	'rule_id',
//				'field2'	=>	'privilege_id',
//			),
//		);
//		
////		$this->z_sortable = true;
//		$this->z_default_field = 'id';
//		$this->z_defaultOrder = 'title asc';
//	}
	
//	protected function getForm()
//	{
//		$resourcesModel = new Z_Model_Resources();
//		$privilegesModel = new Z_Model_Privileges();
//		
//		$form = new Z_Admin_Form;
//    	$form->setAction($this->view->url());
//    	$form->addElement(new Z_Admin_Form_Element_Select('resource_id',array(
//    		'label'		=>	'Ресурс',
//    		'MultiOptions'	=>	$resourcesModel->fetchPairsCat(),
//    		'required'	=>	true,
//    	)));
//    	$form->addElement(new Z_Admin_Form_Element_MultiCheckbox('privileges',array(
//    		'label'		=>	'Привилегия',
//    		'MultiOptions'	=>	$privilegesModel->fetchPairs(array('id','title'),array(),'title asc'),
//    	)));
//    	$form->addElement(new Z_Admin_Form_Element_Select('rule',array(
//    		'label'		=>	'Правило',
//    		'MultiOptions'	=>	array('allow'=>'Разрешить','deny'=>'Запретить'),
//    		'required'	=>	true,
//    	)));
//    	return $form;
//	}

}

