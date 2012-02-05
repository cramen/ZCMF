<?php

class Admin_Acl_ResourcesController extends Z_Admin_Controller_Datacontrol_Abstract
{
    protected $_dependencyModels = array(
        'Z_Model_Resourcebuttons',
        'Z_Model_Resourcecolumns',
        'Z_Model_Resourceconditions',
        'Z_Model_Resourceforms', //Z_Model_Resourceformsparams обрабатываем в коде индивидуально
        'Z_Model_Resourcejoins',
        'Z_Model_Resourcerefers',

    );

    public function addSuccess($param)
    {
        if ($param['resourceId'] && $param['model']) {
            $nameExploded = explode('_', $param['resourceId']);
            $nameExploded = array_map('ucfirst', $nameExploded);

            $controllerName = $nameExploded[count($nameExploded) - 1];
            unset($nameExploded[count($nameExploded) - 1]);
            $ds = DIRECTORY_SEPARATOR;
            $path = APPLICATION_PATH . $ds . 'modules' . $ds . $this->getRequest()->getModuleName() . $ds . 'controllers' . (($pathAdd = implode($ds, $nameExploded)) ? $ds . $pathAdd : '');
            $fileName = $controllerName . 'Controller.php';
            $controllerName = ucfirst($this->getRequest()->getModuleName()) . '_' . (($filePrefix = implode('_', $nameExploded)) ? $filePrefix . '_' : '') . $controllerName . 'Controller';
            $class_file = new Zend_CodeGenerator_Php_Class(array(
                'name' => $controllerName,
                'extendedclass' => 'Z_Admin_Controller_Datacontrol_Abstract',
            ));
            Z_Fs::create_file($path . $ds . $fileName, "<?\n" . $class_file->generate());
        }
    }

    public function getmodelfieldsAction()
    {
        $this->disableRenderAll();
        $model = $this->getRequest()->getParam('model');

        $res = array();
        if (!$model) {

        }
        elseif (class_exists($model))
        {

            $modelObject = new $model();
            if (method_exists($modelObject, 'info')) {
                $cols = $modelObject->info('cols');
                foreach ($cols as $col)
                {
                    $res[] = array(
                        'id' => $col,
                        'label' => $col,
                        'value' => $col,
                    );
                }
            }
        }

        echo Zend_Json::encode($res);
    }

    public function exportAction()
    {
        $this->view->data = Zend_Json::encode($this->_exportResource($this->_getParam('id')));
    }

    protected  function _exportResource($id)
    {
        $model = $this->z_model;
        $modelFormsParams = new Z_Model_Resourceformsparams();

        $resourceArray = $model->find($id)->current()->toArray();
        $resourceArray['depends'] = array();
        $resourceArray['subresources'] = array();

        $nameExploded = explode('_', $resourceArray['resourceId']);
        $nameExploded = array_map('ucfirst', $nameExploded);
        $ds = DIRECTORY_SEPARATOR;
        $path = APPLICATION_PATH . $ds . 'modules' . $ds . $this->getRequest()->getModuleName() . $ds . 'controllers'
                . (($pathAdd = implode($ds, $nameExploded)) ? $ds . $pathAdd : '') . 'Controller.php';

        if (file_exists($path))
        {
            $resourceArray['file_content'] = file_get_contents($path);
        }


        foreach($this->_dependencyModels as $dependModelName)
        {
            $dependModel = new $dependModelName();
            $dependRowsArray = $dependModel->fetchAll(array('resourceid=?'=>$resourceArray['id']))->toArray();
            if ($dependModelName == 'Z_Model_Resourceforms')
            {

                foreach ($dependRowsArray as $key=>$dependRowsArrayItem)
                {
                    $formsParamsArray = $modelFormsParams->fetchAll(array('formid=?'=>$dependRowsArrayItem['id']))->toArray();
                    $dependRowsArray[$key]['params'] = $formsParamsArray;
                }

            }

            $resourceArray['depends'][$dependModelName] = $dependRowsArray;
        }

        $subResources = $model->fetchAll(array('parentid=?'=>$resourceArray['id']),'orderid asc')->toArray();
        if ($subResources)
        {
            foreach($subResources as $subResource)
            {
                $resourceArray['subresources'][] = $this->_exportResource($subResource['id']);
            }
        }


        return $resourceArray;
    }

    public function importAction()
    {
        $model = $this->z_model;

        $parents = $this->z_model->fetchPairsCat();
        $parents[0] = 'Корень';

        $form = new Z_Admin_Form();
        $form->addElement(new Z_Admin_Form_Element_Select('to_resoutce',array(
            'label' =>  'Родитель',
            'multiOptions' => $parents
        )));
        $form->addElement(new Z_Admin_Form_Element_Textarea('data', array(
            'label' => 'Json code',
            'required' => true
        )));
        $this->addSubmitButtonsToForm($form);
        $this->view->form = $form;


        $data = $this->_request->getPost();
        if (!empty($data) && $form->isValid($data))
        {
            $model->getAdapter()->beginTransaction();

            try {
                $dataArray = Zend_Json::decode($data['data']);
            }
            catch (Exception $e)
            {
                Z_FlashMessenger::addMessage('Ошибка Json');
                return;
            }

            try
            {
                $this->_importResource($dataArray,$data['to_resoutce']);
                $model->getAdapter()->commit();
//                $model->getAdapter()->rollBack();
            }
            catch (Exception $e)
            {
                $model->getAdapter()->rollBack();
                Z_FlashMessenger::addMessage('Ошибка импорта');
                Z_FlashMessenger::addMessage($e->getMessage());
                return;
            }

            $form->reset();

        }

    }

    protected function _importResource($data,$to_id)
    {
        $model = $this->z_model;
        $newOrderId = $model->select(false)->from($model,array('MAX(orderid)'))->query()->fetchColumn()+1;


        $modelFormsParams = new Z_Model_Resourceformsparams();

        unset($data['id']);
        $data['parentid'] = $to_id;
        $data['orderid'] = $newOrderId;
        $resourceRow = $model->createRow($data);
        $resourceRowId = $resourceRow->save();

        $nameExploded = explode('_', $data['resourceId']);
        $nameExploded = array_map('ucfirst', $nameExploded);
        $ds = DIRECTORY_SEPARATOR;
        $path = APPLICATION_PATH . $ds . 'modules' . $ds . $this->getRequest()->getModuleName() . $ds . 'controllers'
                . (($pathAdd = implode($ds, $nameExploded)) ? $ds . $pathAdd : '') . 'Controller.php';

        if (!file_exists($path) && isset($data['file_content']) && $data['file_content'])
        {
            Z_Fs::create_file($path,$data['file_content']);
        }

        foreach($data['depends'] as $dependModelName=>$dependDataArray)
        {
            $dependModel = new $dependModelName();
            foreach($dependDataArray as $dependDataRowArray)
            {
                unset($dependDataRowArray['id']);
                $dependDataRowArray['resourceid'] = $resourceRowId;
                $dependDataRow = $dependModel->createRow($dependDataRowArray);
                $dependDataRowId = $dependDataRow->save();
                if ($dependModelName == 'Z_Model_Resourceforms')
                {
                    foreach ($dependDataRowArray['params'] as $param)
                    {
                        unset($param['id']);
                        $param['formid'] = $dependDataRowId;
                        $paramRow = $modelFormsParams->createRow($param);
                        $paramRow->save();
                    }
                }
            }
        }

        if (isset($data['subresources']) && $data['subresources'])
        {
            foreach($data['subresources'] as $subresourceArray)
            {
                $this->_importResource($subresourceArray,$resourceRowId);
            }
        }

    }


}
