<?php

class Admin_Z_DbtablesController extends Z_Admin_Controller_Datacontrol_Abstract
{

    public function buildAction()
    {
        $this->disableRenderView();
        $this->ajaxGo($this->view->url(array('action' => $this->z_defaultAction, 'id' => NULL)));

        $id = $this->_getParam('id');

        $tableModel = $this->z_model;
        $fieldModel = new Z_Model_Dbtablesfields();

        $tableObject = $tableModel->find($id)->current();
        $fieldObjects = $fieldModel->fetchAll(array('dbtable_id=?' => $id), 'orderid asc');

        if ($fieldObjects->count() == 0) {
            $this->dropError('Не заданы поля для таблицы');
            return;
        }

        //генерация запроса на создание таблицы
        $queryString = 'CREATE TABLE  `' . $tableObject->title . '` ( `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,';

        $i = 0;
        foreach ($fieldObjects as $field)
        {
            $i++;
            if ($field->title == 'id') continue;
            $addstr = '`' . $field->title . '`';
            $addstr .= ' ' . strtoupper($field->type);
            $addstr .= $field->len ? '(' . $field->len . ')' : '';
            $addstr .= ($field->is_null ? ' NULL' : ' NOT NULL');
            $addstr .= $field->default == 'asdefine' ? " DEFAULT '" . $field->default_value . "'" : '';
            if ($i < $fieldObjects->count())
                $addstr .= ' ,';
            $queryString .= $addstr;
        }
        foreach ($fieldObjects as $field)
        {
            if ($field->is_index) {
                $queryString .= ' , INDEX (`' . $field->title . '`)';
            }
        }

        //выполнение запроса
        $tableExists = Z_Db_Table::getDefaultAdapter()->listTables();
        $queryString .= ') ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci';

        if (!in_array($tableObject->title, $tableExists))
            Z_Db_Table::getDefaultAdapter()->query($queryString);
        else
            $this->dropError(sprintf('Таблица "%s" уже существует' , $tableObject->title));

        //генерация модели
        Z_Db_Model_Generator::generate($tableObject->title);
    }

    public function rebuildAction()
    {
        $this->disableRenderView();
        $this->ajaxGo($this->view->url(array('action' => $this->z_defaultAction, 'id' => NULL)));

        $id = $this->_getParam('id');

        $tableModel = $this->z_model;
        $fieldModel = new Z_Model_Dbtablesfields();

        $tableObject = $tableModel->find($id)->current();
        $fieldObjects = $fieldModel->fetchAll(array('dbtable_id=?' => $id), 'orderid asc');

        $modelName = 'Site_Model_' . implode('_', array_map('ucfirst', explode('_', $tableObject->title)));

        $model = new $modelName;
        $columns = $model->info('metadata');


        $fieldset = array();
        foreach ($fieldObjects as $field)
        {
            $fieldset[] = $field->title;
            if ($field->title == 'id') continue;
            if (
                array_key_exists($field->title, $columns) &&
                $field->type == $columns[$field->title]['DATA_TYPE']
            ) continue;

            $queryStr = "";

            $queryStr = '`' . $field->title . '`';
            $queryStr .= ' ' . strtoupper($field->type);
            $queryStr .= $field->len ? '(' . $field->len . ')' : '';
            $queryStr .= ($field->is_null ? ' NULL' : ' NOT NULL');
            $queryStr .= $field->default == 'asdefine' ? " DEFAULT '" . $field->default_value . "'" : '';

            if (array_key_exists($field->title, $columns)) {
                $queryStr = 'ALTER TABLE `' . $tableObject->title . '` CHANGE `' . $field->title . '` ' . $queryStr;
            }
            else
            {
                $queryStr = 'ALTER TABLE `' . $tableObject->title . '` ADD ' . $queryStr;
            }
            $this->dropError($queryStr);
            Z_Db_Table::getDefaultAdapter()->query($queryStr);
        }


        foreach ($columns as $column => $columnPropertyes)
        {
            if ($column == 'id') continue;

            if (!in_array($column, $fieldset)) {
                $queryStr = 'ALTER TABLE `' . $tableObject->title . '` DROP `' . $column . '`';
                $this->dropError($queryStr);
                Z_Db_Table::getDefaultAdapter()->query($queryStr);
            }
        }
    }

    public function exportAction()
    {
        $modelTales = new Z_Model_Dbtables();
        $modelFields = new Z_Model_Dbtablesfields();

        $id = $this->_getParam('id');
        if (!$id) {
            $this->dropError('Не пределен id');
        }

        $table = $modelTales->find($id)->current()->toArray();
        $fields = $modelFields->fetchAll(array('dbtable_id=?' => $id))->toArray();

        $data = array(
            'table' => $table,
            'fields' => $fields
        );
        $this->view->data = Zend_Json::encode($data);
    }

    public function importAction()
    {
        $modelTales = new Z_Model_Dbtables();
        $modelFields = new Z_Model_Dbtablesfields();

        $form = new Z_Admin_Form();
        $form->setAction($this->view->url());
        $form->addElement(new Z_Admin_Form_Element_Textarea('data', array(
            'label' => 'Json code',
            'required' => true
        )));
        $this->addSubmitButtonsToForm($form);
        $this->view->form = $form;


        $data = $this->_request->getPost();
        if (!empty($data) && $form->isValid($data)) {
            try {
                $dataArray = Zend_Json::decode($data['data']);
            }
            catch (Exception $e)
            {
                $this->dropError('Ошибка Json');
                return;
            }

            $tableArray = $dataArray['table'];
            unset($tableArray['id']);
            $tableRow = $modelTales->createRow($tableArray);
            $tableId = $tableRow->save();

            foreach ($dataArray['fields'] as $fieldArray)
            {
                unset($fieldArray['id']);
                $fieldArray['dbtable_id'] = $tableId;
                $fieldRow = $modelFields->createRow($fieldArray);
                $fieldRow->save();
            }

            $form->reset();

        }


    }

}
