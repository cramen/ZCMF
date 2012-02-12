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

class Z_Admin_Controller_Datacontrol_Abstract extends Z_Admin_Controller_Action
{
	/**
	 * Действие по умолчанию, на которое будет переадрисован браузер в случае обращения к действию index
	 * @var string
	 */
	protected $z_defaultAction = 'list';

	/**
	 * Заголовок (для постороения хлебных крошек и т.д.)
	 * @var string
	 */
	protected $z_title = NULL;

	/**
	 * Модель, которой будет оперировать контроллер
	 * @var Z_Db_Table
	 */
	protected $z_model = NULL;

	/**
	 * Объект формы
	 * @var Z_Admin_Form
	 */
	protected $z_form = NULL;

	/**
	 * Модель ресурсов
	 * @var Z_Model_Resources
	 */
	protected $z_resourcesModel = NULL;

	/**
	 * Информация о ресурсе
	 * @var Zend_Db_Table_Row
	 */
	protected $z_resourceInfo = NULL;

	protected $z_resourceId = NULL;

	/**
	 * Тип представления. catalog или band
	 * @var string
	 */
	protected $z_datatype = NULL;

	/**
	 * Количество записей на страницу
	 * @var int
	 */
	protected $z_paginate = NULL;

	/**
	 * Массив полей для указания select сортировки
	 * @var array
	 */
	protected $z_order = array();

	/**
	 * группировка для select
	 * @var string
	 */
	protected $z_group = NULL;

	/**
	 * Массив для описания колонок в ленте
	 * по умолчанию установлена колонка title
	 * @var array
	 */
	protected $z_columns = array();

	/**
	 * Массив для описания условий выборки
	 * @var array
	 */
	protected $z_conditions = array();

	/**
	 * Массив для описания присоединяемых таблиц
	 * @var array
	 */
	protected $z_joins = array();

	protected $z_addfields = array();

	/**
	 * Поле по умолчанию для вывода
	 * @var string
	 */
	protected $z_default_field = NULL;

	protected $z_sortable = NULL;
	protected $z_sortable_position = NULL;

	protected $z_delete_on_have_child = NULL;
	protected $z_delete_confirm = NULL;
	protected $z_can_delete = NULL;
	protected $z_can_edit = NULL;
	protected $z_can_add = NULL;

	protected $z_child_resources = array();

	protected $z_refers = array();

	protected $z_additional_buttons = array();
	
	/**
	 * Список полей для индексации при поиске
	 * @var string
	 */
	protected $z_indexate = NULL;

	public function preDispatch()
	{
		parent::preDispatch();


		$this->getResourceInfo();
		$this->exportResourceInfo($this->getResourceInfo());

		//проверка на правильность типа представления (лента или каталог)
		if ($this->z_datatype!='band' && $this->z_datatype!='catalog')
			$this->z_datatype='band';

		$this->view->breadcrumbs = $this->getBreadCrumbs();
		$this->view->table = $this->z_model->info('name');
	}

	public function indexAction()
	{
		$this->disableRenderView();
		if ($this->z_defaultAction == 'index')
		{
			$this->dropError('Действие по умолчанию не может быть "index"');
			return;
		}
		$this->ajaxGo($this->view->url(array('action'=>$this->z_defaultAction)));
	}

	public function addAction()
	{
		$this->setViewPathes();
		$form = $this->getFormInstance();
		
		$data = $this->_request->getPost();
		$data = $this->addOverridePrevalidate($data);
		
		if (!empty($data) && $form->isValid($data))
		{
			//получение данных из формы
			$dataForm = $form->getValues($data);
			if ($this->z_datatype=='catalog' && !isset($dataForm['parentid']))
				$dataForm['parentid'] = $this->_getParam('parentid',0);

			//если есть дополнительные поля, то добавляем их в массив для insert
			if (!empty($this->z_addfields))
				$dataForm = array_merge($this->z_addfields,$dataForm);

			if (!$this->addCheck($dataForm))
			{
				$this->disableRenderView();
				Z_FlashMessenger::addMessage('Нельзя добавить новый элемент!');
				return;
			}

			//оверрайд
			$dataForm = $this->addOverride($dataForm);
			//добавление элемента в таблицу
			$item = $this->z_model->createRow($dataForm);
			$item->save();
			$id = $item->id;
			//саксэс
			$this->addSuccess(array_merge($item->toArray(),$dataForm));
			$this->addToIndex($item->toArray());


			//добавление связей многие ко многим
			if (!empty($this->z_refers))
				foreach ($this->z_refers as $referName=>$refer)
				{
					$referData = $dataForm[$referName]?$dataForm[$referName]:array();
					$referModel = new $refer['model'];
					foreach ($referData as $referDataEl)
					{
						$referModel->createRow(array(
						$refer['field1']  =>  $id,
						$refer['field2']  =>  $referDataEl
						))->save();
					}
				}

			//если список сортируемый, то устанавливаем orderid для элемента
			if ($this->z_sortable)
			{
				$select = $this->z_model->select(true);
				$select->reset('columns')->columns(
				($this->z_sortable_position=='top'?'MIN':'MAX').'(orderid)'
				)->where('id!=?',$id);
				$orderid = $select->query()->fetchColumn();
				if (!is_numeric($orderid))
					$orderid = $id;
				else
					$this->z_sortable_position=='top'?$orderid--:$orderid++;
				$item->orderid = $orderid;
				$item->save();
			}


			if (isset($data['z-ajax-form-applyonly']))
			{
				$this->disableRenderView();
				$this->ajaxGo($this->view->url(array('action'=>'edit','id'=>$id)));
			}
			else
			{
				$this->disableRenderView();
				$this->ajaxGo($this->view->url(array('action'=>$this->z_defaultAction)));
			}
		}

		$this->view->form = $form;
	}

	public function editAction()
	{
		$this->setViewPathes();

		$form = $this->getFormInstance();

		$id = $this->_getParam('id');

		$dataRequest = $this->_request->getPost();
		unset($dataRequest['id']);
		//если есть данные из реквеста
		if (!empty($dataRequest))
		{
			$dataRequest = $this->editOverridePreValidate($dataRequest);
			if ($form->isValid($dataRequest))
			{
				$dataForm = $form->getValues($dataRequest);

				//если есть дополнительные поля, то добавляем их в массив для insert
				if (!empty($this->z_addfields))
					$dataForm = array_merge($this->z_addfields,$dataForm);

				if (!$this->editCheck($dataForm))
				{
					$this->disableRenderView();
					Z_FlashMessenger::addMessage('Нельзя изменить этот элемент!');
					return;
				}

				//оверрайд
				$dataForm = $this->editOverride($dataForm);

				$item = $this->z_model->fetchRow(array('id=?'=>$id));
				$item->setFromArray($dataForm);
				$item->save();
				//саксэс
				$this->editSuccess(array_merge($item->toArray(),$dataForm));
				$this->addToIndex($item->toArray());

				//добавление связей многие ко многим
				if (!empty($this->z_refers))
					foreach ($this->z_refers as $referName=>$refer)
					{
						$referModel = new $refer['model'];
						$referData = isset($dataForm[$referName])?$dataForm[$referName]:array();
						$referModel->delete(array(
						$refer['field1'].'=?'		=>	$id
						));
						foreach ($referData as $referDataEl)
						{
							$referModel->createRow(
							array(
							$refer['field1']		=>	$id,
							$refer['field2']		=>	$referDataEl
							))->save();
						}
					}

				if (!isset($dataRequest['z-ajax-form-applyonly']))
				{
					$this->disableRenderView();
					$this->ajaxGo($this->view->url(array('action'=>$this->z_defaultAction,'id'=>NULL)));
				}
			}
			else
			{
				$dataForm = $form->getValidValues($dataRequest);
				$form->setDefaults($dataForm);
			}
		}
		else
		{
			$dataDb = $this->z_model->fetchRow(array('id=?'=>$id));
			$defaultData = $dataDb->toArray();

			//установка связей многие ко многим
			if (!empty($this->z_refers))
				foreach ($this->z_refers as $referName=>$refer)
				{
					$referModel = new $refer['model'];
					$referDataRows = $referModel->fetchAll(array(
					$refer['field1'].'=?'		=>	$id,
					));
					$defaurlDataRefer = array();
					foreach ($referDataRows as $referDataRow)
					{
						$defaurlDataRefer[] = $referDataRow->$refer['field2'];
					}
					$defaultData[$referName] = $defaurlDataRefer;
				}

			$defaultData = $this->editPreset($defaultData);
			$form->setDefaults($defaultData);
		}


		$this->view->form = $form;
	}

	public function multyeditAction()
	{
		$this->setViewPathes();
		Z_FlashMessenger::addMessage('Множественное редактирование не поддерживается');
		$this->disableRenderView();
	}

	public function deleteAction()
	{
		$this->setViewPathes();
		$this->disableRenderView();//отключение вывода
		//проверка на возможность удаления
		if (!$this->z_can_delete)
		{
			Z_FlashMessenger::addMessage('Удаление запрещено!');
			return;
		}
		//получение списка удаляемых элементов
		if (!$this->_getParam('id'))
			$ids = $this->_getParam('ids');
		else
			$ids = array($this->_getParam('id'));
        $ids = is_array($ids)?$ids:array($ids);


		//если есть подтверждение на удаление или оно не требуется, то удаляем
		if (($this->z_delete_confirm && $this->ajaxConfirm('Удалить?','confirmed',array('ids:['.implode(',',$ids).']'))) || !$this->z_delete_confirm)
		{
			//получение списка полей, с файлами на удаление
			$modelForm = new Z_Model_Resourceforms();
			$formFileitems = $modelForm->fetchAll(array('resourceid=?'=>$this->getResourceInfo()->id,'is_file=?'=>1));
			$storage = new Z_File_Storage();

			$deletedItemsCount = 0;
			foreach ($ids as $id)
			{
				$item = $this->z_model->fetchRow(array('id=?'=>$id));
				$itemArray = $item->toArray();

				if (!$this->deleteCheck($itemArray))
				{
					Z_FlashMessenger::addMessage('Удаление записи "'.$itemArray[$this->z_default_field].'" Запрещено!');
					continue;
				}

				//проверка на наличие подразделов в каталоге
				$haveSubcat = false;
				if ($this->z_datatype=='catalog')
				{
					$subCatCount = $this->z_model->select(true)
					->reset('columns')
					->columns('count(*)')
					->where('parentid=?',$item->id)->query()->fetchColumn();
					if ($subCatCount>0)
					{
						$haveSubcat = true;
						Z_FlashMessenger::addMessage('Раздел "'.$itemArray[$this->z_default_field].'" имеет подразделы');
					}
				}

				//проверка на наличие дочерних элементов
				$haveChild = false;
				if ($this->z_child_resources && !$this->z_delete_on_have_child)
				{
					foreach ($this->z_child_resources as $resource)
					{
						$childModel = new $resource['model'];
						$childItem = $childModel->fetchRow(array($resource['parent_field'].'=?'=>$item->id));
						if ($childItem)
						{
							$haveChild = true;
							Z_FlashMessenger::addMessage('"'.$itemArray[$this->z_default_field].'" имеет подчиненные "'.$resource['title'].'"');
						}
					}
				}

				//проверка на возможность удаления элемента
				if ((!isset($itemArray['z_can_delete']) || (isset($itemArray['z_can_delete']) && $itemArray['z_can_delete'])) && (!$haveChild || $this->z_delete_on_have_child) && (!$haveSubcat))
				{
					//удаление связей многие ко многим
					if (!empty($this->z_refers))
						foreach ($this->z_refers as $referName=>$refer)
						{
							$referModel = new $refer['model'];
							$referModel->delete(array(
							$refer['field1'].'=?'		=>	$item->id
							));
						}
					$delData = $item->toArray();
					$item->delete();

					foreach ($formFileitems as $formFileitem)
					{
                        if (isset($delData[$formFileitem->field]))
						    $storage->removeFileDir($delData[$formFileitem->field]);
					}
					$this->deleteSuccess($delData);
					$this->deleteFromIndex($delData['id']);

					$deletedItemsCount++;
				}
				else
					Z_FlashMessenger::addMessage('Удаление записи "'.$itemArray[$this->z_default_field].'" Запрещено!');
			}
			if ($deletedItemsCount>0) $this->ajaxGo($this->view->url(array('action'=>$this->z_defaultAction,'id'=>NULL,'confirmed'=>NULL)));
		}

	}

	public function reorderAction()
	{
		$this->setViewPathes();
		$this->disableRenderView();

		$ids = $this->_getParam('ids');
		$id = $this->_getParam('id');
		$direction = $this->_getParam('direction');

		if ($ids && !empty($ids))
		{
			$items = $this->z_model->find($ids);
			$orderids = array();
			foreach ($items as $item)
				$orderids[] = $item->orderid;
			sort($orderids);
			$array_edit = array_combine($ids,$orderids);
			foreach ($items as $item)
			{
				if ($item->orderid != $array_edit[$item->id])
				{
					$item->orderid = $array_edit[$item->id];
					$item->save();
				}
			}
		}
		elseif ($id)
		{
			if ($direction!='up' && $direction!='down' && $direction!='top' && $direction!='bottom') return;

			$item = $this->z_model->fetchRow(array('id=?'=>$id));
			if (!$item) return;

			if ($direction=='up')
				$item2 = $this->z_model->fetchRow(array('orderid<?'=>$item->orderid),'orderid desc');
			elseif($direction=='down')
				$item2 = $this->z_model->fetchRow(array('orderid>?'=>$item->orderid),'orderid asc');
			elseif($direction=='top')
				$item2 = $this->z_model->fetchRow(NULL,'orderid asc');
			elseif($direction=='bottom')
				$item2 = $this->z_model->fetchRow(NULL,'orderid desc');

			if (!$item2) return;

			if ($direction=='up' || $direction=='down')
			{
				$tmp = $item->orderid;
				$tmp2 = $item2->orderid;

				$item->orderid = $tmp2;
				$item2->orderid = $tmp;

				$item->save();
				$item2->save();
			}
			elseif ($direction=='top' || $direction=='bottom')
			{
				$newOrderId = $direction=='top'?$item2->orderid-1:$item2->orderid+1;
				$item->orderid = $newOrderId;
				$item->save();
			}
			$this->ajaxGo($this->view->url(array(
			'action'		=>	'list',
			'direction'		=>	NULL,
			'id'		=>	NULL,
			)));
		}

	}

	public function zrememberopenstateAction()
	{

		$controller = $this->getResourceInfo()->resourceId;
		$nameSpaceId = 'z_'.$controller.'_openstate';
		$nameSpace = new Zend_Session_Namespace($nameSpaceId);
		$id = $this->_getParam('z_catalog_sysparentid',0);
//    	$id = $this->_getParam('id');
		$state = $this->_getParam('state');
		if ($id && $state && $state!='null')
		{
			if ($state == 'block')
				$nameSpace->$id = true;
			else
				$nameSpace->$id = false;
		}

		$this->disableRenderView();
	}

	public function listAction()
	{
		$this->setViewPathes();
		$tableName = $this->z_model->info('name');
		$select = Zend_Db_Table_Abstract::getDefaultAdapter()->select()->from($tableName);

		$this->setJoinsToSelect($select,$this->z_joins);
		$this->setConditionsToSelect($select,$this->z_conditions);
		$this->setOrdersToSelect($select,$this->z_order);
		$this->setGroupToSelect($select,$this->z_group);

//		echo $select->assemble();

		if ($this->z_datatype=='band')
		{
			$pagitatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
			$paginator = new Zend_Paginator($pagitatorAdapter);
			$paginator->setItemCountPerPage($this->z_paginate);
			$paginator->setPageRange(10);
			$paginator->setCurrentPageNumber($this->_getParam('page',1));

			$this->view->paginator = $paginator;
		}
		elseif ($this->z_datatype=='catalog')
		{
			$catalogParent = $this->_getParam('z_catalog_sysparentid',0);

			$select->where('parentid=?',$catalogParent);

			$items = $select->query()->fetchAll();

			foreach ($items as $key=>$item)
			{
				$items[$key]['z_have_subcat'] = $this->z_model->select(true)
				->reset(Zend_Db_Select::COLUMNS)
				->where('parentid=?',$item['id'])
				->columns('COUNT(*)')
				->query()->fetchColumn();
			}

			$this->view->items = $items;
			$this->view->catalogParent = $catalogParent;
			$this->getRequest()->setParam('z_catalog_sysparentid',NULL);
			if ($catalogParent!=0)
			{
				$this->setTarget('#catalog'.$catalogParent);
				$this->_helper->viewRenderer->setViewScriptPathSpec('datacontrol/'.$this->z_datatype.'/sublist.:suffix');
			}
		}

	}

	public function getfilterurlAction()
	{
		$this->disableRenderView();
		$paramsArray = array('action'=>'list');
		foreach ($this->z_columns as $field=>$column)
		{
			$paramName = 'filter_'.$this->z_resourceId.'_'.$field;
			$filterValue = $this->_getParam($paramName,NULL);
			if($filterValue == '') $filterValue=NULL;
			if ($filterValue!==NULL)
			{
				$filterValue = $this->_getParam($paramName);
				if (!$this->_request->getUserParam($paramName))
				{
					$filterValue = urlencode($filterValue);
					$filterValue = base64_encode($filterValue);
				}
				$paramsArray[$paramName] = $filterValue;
			}
		}
		$this->ajaxGo($this->view->url($paramsArray));
	}

    public function indexateAction()
    {
        if ($ids = $this->getRequest()->getParam('ids'))
        {
            $data = $this->z_model->fetchAll(array('id in (?)'=>$ids));
        }
        else
        {
            $data = $this->z_model->fetchAll();
        }
        $i=0;
        foreach ($data as $el)
        {
            $i++;
            $this->addToIndex($el->toArray());
        }
        Z_FlashMessenger::addMessage('Переиндексировано документов: '.$i);
        $this->ajaxGo($this->view->url(array('action'=>$this->z_defaultAction)));
        $this->disableRenderView();
    }

	public function addOverridePreValidate($param)
	{
		return $param;
	}

	public function addCheck($param)
	{
		return true;
	}

	public function addOverride($param)
	{
		return $param;
	}

	public function addSuccess($param)
	{
	}

	public function editOverridePreValidate($param)
	{
		return $this->addOverridePreValidate($param);
	}

	public function editCheck($param)
	{
		return $this->addCheck($param);
	}

	public function editOverride($param)
	{
		return $this->addOverride($param);
	}

	public function editPreset($param)
	{
		return $param;
	}

	public function editSuccess($param)
	{
		$this->addSuccess($param);
	}

	public function deleteCheck($param)
	{
		return true;
	}

	public function deleteSuccess($param)
	{
		return true;
	}

	protected function addToIndex($data)
	{
		if (trim($this->z_indexate)=='') return;
		if (!isset($data['id'])) return;
		$fields = explode(';',trim($this->z_indexate));
		
		$searchIndex = Z_Search::getInstance();

		//создаем документ
		$doc = new Zend_Search_Lucene_Document();
		$doc->addField(Zend_Search_Lucene_Field::keyword('_id', $data['id']));
		$doc->addField(Zend_Search_Lucene_Field::keyword('_type', $this->z_model->info('name')));
		foreach ($fields as $field)
		{
			if (isset($data[$field]))
			$doc->addField(Zend_Search_Lucene_Field::text($field, $data[$field]));
		}
		
		//удаляем старый документ
		$hits = $searchIndex->find('_id:'.$data['id'].' AND _type:'.$this->z_model->info('name'));
		foreach ($hits as $hit)
		{
			$searchIndex->delete($hit->id);
		}
		
		//добавляем документ
		$searchIndex->addDocument($doc);
	}

	protected function deleteFromIndex($id)
	{
		$searchIndex = Z_Search::getInstance();

		//удаляем документ
		$hits = $searchIndex->find('_id:'.$id);
		foreach ($hits as $hit)
		{
			$searchIndex->delete($hit->id);
		}
	}
	
	/**
	 * @return Z_Admin_Form
	 */
	protected function getForm(Z_Admin_Form $form)
	{
	}

	protected function addSubmitButtonsToForm(Z_Admin_Form $form)
	{
		//добавляем кнопки сохранения и применения
		$form->addElement(new Z_Admin_Form_Element_Submit('submit',array(
		'label'		=>	'Сохранить',
		'class'		=>	'z-form-submit',
		)));
		$form->addElement(new Z_Admin_Form_Element_Submit('apply',array(
		'label'		=>	'Применить',
		'class'		=>	'z-form-apply',
		)));
		$form->addDisplayGroup(array('submit','apply'),'z_form_buttons',array(
		'class'		=>	'z_form_buttons'
		));

		$form->getElement('submit')->removeDecorator('Label');

	}

	protected function getFormInstance()
	{
		if ($this->z_form === NULL)
		{
			$this->z_form = new Z_Admin_Form();

			//добавляем элементы формы на основании даннх из БД
			$modelForms = new Z_Model_Resourceforms();
			$modelFormsParams = new Z_Model_Resourceformsparams();
			$elements = $modelForms->fetchAll(array('resourceid=?'=>$this->z_resourceInfo->id),'orderid asc');
			$isRoot = Z_Auth::getInstance()->getUser()->getRole()=='root';
			foreach ($elements as $element)
			{
				if ($element->show_check)
				{
//    				try{
					if (!eval($element->show_check)) continue;
//    				}
//    				catch (Exception $e){
//    					continue;
//    				}
				}
				$elementClass = 'Z_Admin_Form_Element_'.ucfirst($element->type);
				if (($element->only_for_root && $isRoot) || !$element->only_for_root)
				{
					$elementOptions = array(
					'label'			=>	$element->label,
					'description'	=>	$element->description,
					'required'		=>	$element->required,
					'value'			=>	$element->value
					);
					$params = $modelFormsParams->fetchAll(array('formid=?'=>$element->id));
					foreach ($params as $param)
					{
						$elementOptions[$param->title] = $param->is_eval?eval($param->value):$param->value;
					}
					$this->z_form->addElement(new $elementClass($element->field,$elementOptions));
				}
			}

			//пользовательское доопределение формы
			$this->getForm($this->z_form);


			//разбиваем элементы на группы
			$groupArray = array();
			foreach ($this->z_form->getElements() as $el)
			{
				if ($group = $el->getAttrib('displayGroup'))
				{
					$groupId = Z_Transliterator::translateCyr($group);
					if (!isset($groupArray[$group]))
					{
						$groupArray[$group] = array();
					}
					$groupArray[$group][] = $el->getName();
				}
			}

			foreach ($groupArray as $groupKey=>$groupEl)
			{
				$this->z_form->addDisplayGroup($groupEl,Z_Transliterator::translateCyr($groupKey),array('legend'=>$groupKey));
			}

			$this->addSubmitButtonsToForm($this->z_form);

			$this->z_form->getElement('submit')->removeDecorator('Label');
			if (!$this->z_form->getAction()) $this->z_form->setAction($this->view->url(array('id'=>$this->_getParam('id'))));

            $elements = $this->z_form->getElements();
            if (count($elements))
            {
                $keys = array_keys($elements);
                $key = $keys[0];
                $element = $elements[$key];
                $elementId = $element->getId();
                jQuery::evalScript('$("#'.$elementId.'").focus();');
            }

		}
		return $this->z_form;
	}



	public function getControllerName()
	{
		$controllerName = get_class($this);
		$module = ucfirst($this->getRequest()->getModuleName());
		$controllerName = str_replace($module.'_','',$controllerName);
		$controllerName = str_replace('Controller','',$controllerName);
		$controllerName = strtolower($controllerName);
		return $controllerName;
	}

	/**
	 * Возвращает информацию о текущем ресурсе
	 * @return Zend_Db_Table_Row
	 */
	protected function getResourceInfo()
	{
		if (NULL === $this->z_resourceInfo)
		{
			if ($this->z_resourceInfo) return $this->z_resourceInfo;
			$this->z_resourceInfo = $this->getResourceModel()->fetchRow(array('resourceId=?'=>$this->getControllerName()));
			if (!$this->z_resourceInfo) throw new Exception('Нет описания ресурса в базе данных');
		}
		return $this->z_resourceInfo;
	}

	/**
	 * Возвращает модель ресурсов
	 * @return Z_Model_Resources
	 */
	protected function getResourceModel()
	{
		if (NULL === $this->z_resourcesModel)
			$this->z_resourcesModel = new Z_Model_Resources();
		return $this->z_resourcesModel;
	}

	/**
	 * Устанавливает каталоги с представлениями
	 */
	protected function setViewPathes()
	{
		$this->view->addBasePath(APPLICATION_PATH."/../library/Z/templates");
		$this->_helper->viewRenderer->setViewScriptPathSpec('datacontrol/'.$this->z_datatype.'/:action.:suffix');
	}

	/**
	 * Устанавливает все настройки контроллера на основании информации о ресурсе
	 */
	protected function exportResourceInfo(Zend_Db_Table_Row $info)
	{
		$fieldsToMove = array(
		'resourceId',
		'title',
		'datatype',
		'default_field',
		'paginate',
		'group',
		'can_delete',
		'can_edit',
		'can_add',
		'delete_confirm',
		'delete_on_have_child',
		'sortable',
		'sortable_position',
		'indexate',
		);

		//установка моделей
		if (!$info->model) throw new Exception('Не указана модель');
		$modelName = $info->model;
		$this->z_model			=	new $modelName();

		//колонки
		$modelResourcecolumns = new Z_Model_Resourcecolumns();
		$columns = $modelResourcecolumns->fetchAll(array('resourceid=?'=>$info->id),'orderid');
		foreach ($columns as $column)
		{

			//добавление фильтров в условие (если фильтры имеются)
			$filterValue = $this->_getParam('filter_'.$info->resourceId.'_'.$column->field,NULL);
			$filterValue = $filterValue?urldecode(base64_decode($filterValue)):$filterValue;
			if ($column->filter_query && $filterValue!==NULL)
			{
				$this->z_conditions[] = array(
				'condition'		=>	$column->filter_query,
				'value'			=>	strpos($column->filter_query,'LIKE')>0?'%'.$filterValue.'%':$filterValue
				);
				$info->sortable = false;
			}

			//добавление сортировки по параметрам
			$orderdir	=	'';
			$ordered	=	false;
			if ($column->orderlink && $this->_getParam($info->resourceId.'_orderfield')==$column->field)
			{
				$orderdir = $this->_getParam($info->resourceId.'_orderdir')=='desc'?'DESC':'';
				$this->z_order[] = $column->field.($orderdir?' '.$orderdir:' ASC');
				$ordered	=	true;
				$info->sortable = false;
			}

			$this->z_columns[$column->field] = array(
			'title'			=>	$column->title,
			'width'			=>	$column->width,
			'orderlink'		=>	$column->orderlink?true:false,
			'orderdir'		=>	$orderdir,
			'ordered'		=>	$ordered,
			'template'		=>	$column->template?$column->template:false,
			'eval'			=>	$column->eval?$column->eval:false,
			'escape'		=>	$column->escape?true:false,
			'filter'		=>	$column->filter_query?$column->filter_query:false,
			'filter_value'	=>	$filterValue,
			'filter_items'	=>	$column->filter_items?eval($column->filter_items):false,
			'on_have_subcat'=>	$column->on_have_subcat,
			'visible'		=>	$column->visible,
			);
		}

		//добавление ссылок на дочерние таблицы
		$childResources = $this->getResourceModel()->fetchAll(array('parentid=?'=>$info->id,'parent_field!=?'=>'','model!=?'=>''),'orderid');
		foreach ($childResources as $childResource)
		{
			//проверка на правдо доступа.
			//Если нет доступа на этот ресурс, пропускаем добавление ссылки на него
			if (!Z_Acl::getInstance()->isAllowed(Z_Auth::getInstance()->getUser()->getRole(), $childResource->resourceId, 'list'))
				continue;

			$this->z_child_resources[] = $childResource->toArray();
			$this->z_columns[$childResource->resourceId.'_resource'] = array(
			'title'			=>	$childResource->title,
			'eval'			=>	'return "<a class=\"z-ajax\" href=\"".$this->url(array("controller"=>"'.$childResource->resourceId.'","action"=>"list","'.$childResource->resourceId.'_parentid"=>{{id}},"z_catalog_sysparentid"=>NULL))."\">'.$childResource->title.'</a>";',
			'on_have_subcat'=>	$childResource->on_have_subcat,
			);
		}
		$this->view->columns = $this->z_columns;

		//Джойны
		$modelResourcejoins = new Z_Model_Resourcejoins();
		$joins = $modelResourcejoins->fetchAll(array('resourceid=?'=>$info->id),'orderid');
		foreach ($joins as $join)
		{
			$modelName = $join->model;
			if (class_exists($modelName))
			{
				$model = new $modelName();
				$joinTableName = $model->info('name');
			}
			else
			{
				$joinTableName = $modelName;
			}

			$fields = array();
			$fieldsArray = explode(';',$join->fields);
			foreach ($fieldsArray as $fiendPair)
			{
				$fieldPairArray			= explode('|',$fiendPair);
				$realfield				= $fieldPairArray[0];
				$logicfield				= isset($fieldPairArray[1])?$fieldPairArray[1]:$realfield;
				$fields[$logicfield]	= $realfield;
			}
			$template = new Z_View_Template($join->condition,array(
			'table'		=>	$this->z_model->info('name'),
			'jointable'	=>	$joinTableName
			));
			$this->z_joins[] = array(
			'table'		=>	$joinTableName,
			'condition'	=>	$template->render(),
			'fields'	=>	$fields
			);
		}

		//условия
		$modelResourceconditions = new Z_Model_Resourceconditions();
		$condidtions = $modelResourceconditions->fetchAll(array('resourceid=?'=>$info->id));
		foreach ($condidtions as $condidtion)
		{
			$this->z_conditions[] = array('condition'=>$condidtion->condition,'value'=>$condidtion->value);
		}

		//условия при наличии родительского ресурса
		if ($info->parent_field && $parentid = $this->_getParam($info->resourceId.'_parentid'))
		{
			$this->z_conditions[] = array('condition'=>$this->z_model->info('name').'.'.$info->parent_field.'=?','value'=>$parentid);
			$this->z_addfields[$info->parent_field] = $parentid;
		}

		$modelRasourceRefers = new Z_Model_Resourcerefers();
		foreach ($modelRasourceRefers->fetchAll(array('resourceid=?'=>$info->id)) as $refer)
		{
			$this->z_refers[$refer->field] = $refer->toArray();
		}

		//копирование параметров во вью и в атрибуты класса
		if ($info->sortable_position!='top' && $info->sortable_position!='bottom') $info->sortable_position='bottom';
		foreach ($fieldsToMove as $field)
		{
			$zField = 'z_'.$field;
			if ($this->$zField !== NULL)
			{
				$info->$field = $this->$zField;
			}
			$this->$zField = $info->$field;
			$this->view->$field = $info->$field;
		}

		//сортировка
		if ($this->z_sortable)
		{
			$this->z_order[] = 'orderid asc';
		}
		else
		{
			$orderArray = explode(';',$info->order);
			foreach ($orderArray as $order) if ($order = trim($order)) $this->z_order[] = $order;
		}

		//доп кнопки вверху
		$modelButtons = new Z_Model_Resourcebuttons();
		$buttons = $modelButtons->fetchAll(array(
		'resourceid=?'	=>	$info->id
		),'orderid');
		foreach ($buttons->toArray() as $button)
		{
			$button['url'] = eval($button['url']);
			$this->z_additional_buttons[] = $button;
		}
//    	$this->z_additional_buttons = array_merge($buttons->toArray(),$this->z_additional_buttons);
		$this->view->additional_buttons = $this->z_additional_buttons;

	}

	/**
	 * Устанавливает джойны в запрос
	 * @param Zend_Db_Select $select
	 * @param array $joins
	 */
	protected function setJoinsToSelect(Zend_Db_Select $select,$joins)
	{
		foreach ($joins as $join)
		{
			$select->joinLeft($join['table'],$join['condition'],$join['fields']);
		}
	}


	/**
	 * Устанавливает условия выборки в запрос
	 * @param Zend_Db_Select $select
	 * @param array $conditions
	 */
	protected function setConditionsToSelect(Zend_Db_Select $select,$conditions)
	{
		foreach ($conditions as $condition)
		{
			$select->where($condition['condition'],$condition['value']);
		}
	}

	/**
	 * Устанавтивает сортировку в запрос
	 * @param Zend_Db_Select $select
	 * @param array $conditions
	 */
	protected function setOrdersToSelect(Zend_Db_Select $select,$orders)
	{
		foreach ($orders as $order)
		{
			$select->order($order);
		}
	}

	/**
	 * Устанавтивает группировку в запрос
	 * @param Zend_Db_Select $select
	 * @param string $conditions
	 */
	protected function setGroupToSelect(Zend_Db_Select $select,$group)
	{
		if ($group = trim($group))
			$select->group($group);
	}

	protected function getBreadCrumbs($id=NULL,$ret=array(),$reset=array())
	{
		if ($id==NULL)
		{
			$id = $this->getResourceInfo()->id;
		}
		if ($id == $this->getResourceInfo()->id)
			$resource = $this->getResourceInfo();
		else
		{
			$resource = $this->getResourceModel()->fetchRow(array('id=?'=>$id));
		}

		if ($resource)
		{
			if (!isset($this->modelColumns))
				$this->modelColumns = new Z_Model_Resourcecolumns();
			$unsetColumns = $this->modelColumns->fetchAll(array(
			'filter_query!=?'	=>	'',
			'resourceid=?'		=>	$resource->id
			));
			foreach ($unsetColumns as $unsetColumn)
			{
				$reset['filter_'.$resource->resourceId.'_'.$unsetColumn->field] = NULL;
			}
//    		$reset['page'] = NULL;

			if ($resource->parentid>0)
				$ret = $this->getBreadCrumbs($resource->parentid,$ret,$reset);

			$description = '';
			if (count($ret) && $ret[count($ret)-1]['model'] && $ret[count($ret)-1]['default_field'] && $pid = $this->_getParam($resource->resourceId.'_parentid'))
			{
				$lastCrumb = $ret[count($ret)-1];
				$resModel = new $lastCrumb['model'];
				if ($lastItem = $resModel->find($pid)->current())
				{
					$ret[count($ret)-1]['description'] = $lastItem->$lastCrumb['default_field'];
					$ret[count($ret)-1]['url_array'] = array_merge($ret[count($ret)-1]['url_array'],$reset);
				}
				foreach ($ret as $key=>$el)
				{
					if ($el['model']) $ret[$key]['url_array'][$resource->resourceId.'_parentid']=NULL;
				}
			}

			$ret[] = array(
			'title'			=>	$resource->title,
			'default_field'	=>	$resource->default_field,
			'resourceId'	=>	$resource->resourceId,
			'model'			=>	$resource->model,
			'description'	=>	NULL,
			'url_array'		=>	$resource->model?array('controller'=>$resource->resourceId,'action'=>'list','id'=>NULL,'parentid'=>NULL):array(),
			);
		}


		return $ret;
	}
}
