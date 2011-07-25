<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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
class Z_Admin_Form_Element_File extends Zend_Form_Element_File
{
	protected 	$_storedFileId = null;
	protected $_storedFileDecName = null;
	protected $_isReceived = false;
	public function  __construct($spec, $options = null)
	{
		$this->_storedFileDecName = $spec . "_stored";
		parent::__construct($spec, $options);
	}
//------------------------------------------------------------------------------
    /**
     * Load default decorators
     *
     * @return void
     */
	public function loadDefaultDecorators()
	{
			if ($this->loadDefaultDecoratorsIsDisabled ())
		{
			return;
		}
		
		$this->class = $this->class." ui-widget-content ui-corner-all";

		$decorators = $this->getDecorators();
		if (empty($decorators)) {
			$d = new Z_Admin_Form_Decorator_StoredFile(array('name' => $this->_storedFileDecName, 'value' => $this->_storedFileId));
	    	$this->setDecorators(array(
	    		array('Errors',array('class'=>Z_Admin_Form::$_errorDecoratorClass)),
	    		array('Description', array ('tag' => 'p','class' => 'z-form-description','escape'=>false)),
	    		'File',
	    		$d,
	    		array('HtmlTag', array('tag' => 'div','class'=>'z-form-inputItem')),
	    		array('Label'),
	    	));
		}
	}
//------------------------------------------------------------------------------  
	/**
	 * Валидация элемента file
	 * @param  $value - значение элемента для валидации
	 * @param array $context - контекст валидации
	 * @return bool. true - валидация успешно пройдена
	 */
	public function isValid($value, $context = null)
    {
		if ($this->_validated) {
            return true;
        }
        $adapter    = $this->getTransferAdapter();
        $translator = $this->getTranslator();
		$this->_processStoredFile($context);
        if ($translator !== null) {
            $adapter->setTranslator($translator);
        }
        if (!$this->isRequired() || $this->_storedFileId != null) {
            $adapter->setOptions(array('ignoreNoFile' => true), $this->getName());
        } else {
            $adapter->setOptions(array('ignoreNoFile' => false), $this->getName());
            if ($this->autoInsertNotEmptyValidator() and
                   !$this->getValidator('NotEmpty'))
            {
                $validators = $this->getValidators();
                $notEmpty   = array('validator' => 'NotEmpty', 'breakChainOnFailure' => true);
                array_unshift($validators, $notEmpty);
                $this->setValidators($validators);
            }
        }
        if($adapter->isValid($this->getName())) {
            $this->_validated = true;
			if(!$this->_isReceived)
				$this->_isReceived = (bool) $this->getValue();
			$this->_value = $this->_storedFileId;
            return true;
        }
        $this->_validated = false;
        return false;
    }
//------------------------------------------------------------------------------
	/**
	 * Прием файла(ов) по протоколу через используемый адаптер (по умолчанию Z_File_Transfer_Adapter_Http)
	 * @return bool. true - файл(ы) успешно принят
	 */
    public function receive()
    {
        if($this->_isReceived)
			return true;
		if (!$this->_validated) {
            if (!$this->isValid($this->getName())) {
                return false;
            }
        }
        $adapter = $this->getTransferAdapter();
        if ($adapter->receive($this->getName(), $this->_storedFileId))
		{
			if($adapter instanceof Z_File_Transfer_Adapter_Http)
			{
				$this->_value = $adapter->getProcessedFileId($this->getName());
				$this->_resetStoredFileDecorator($this->_value);
			}
			$this->_isReceived = true;
            return true;
        }
        return false;
    }
//------------------------------------------------------------------------------
	/**
	 * Получить идентификатор контролируемого сохраненного файла
	 * @return int - id контролируемого сохраненного файла
	 */
	public function getStoredFile()
	{
		return $this->_storedFileId;
	}
//------------------------------------------------------------------------------
	/**
	 * Получить текущий адаптер передачи файлов. Если не установлен - установка адаптера по умолчанию (Z_File_Transfer_Adapter_Http)
	 * @return Z_File_Transfer_Adapter_Abstract
	 */
    public function getTransferAdapter()
    {
        if (null === $this->_adapter) {
            $this->setTransferAdapter(new Z_File_Transfer_Adapter_Http());
        }
        return $this->_adapter;
    }
//------------------------------------------------------------------------------
	/**
	 * Привязка идентификатора сохраненного файла к контролу
	 * @param int $val - id файла в базе сохраненных файлов
	 */
	public function setValue($val)
	{
		$this->_resetStoredFileDecorator((int) $val);
	}
//------------------------------------------------------------------------------
    /**
     * Processes the file, returns null or the stored file id
     * For the complete path, use getFileName
     *
     * @return null|int
     */
    public function getValue()
    {
        if ($this->_value !== null) {
            return $this->_value;
        }
        

        $content = $this->getTransferAdapter()->getFileName($this->getName());
        if (empty($content)) {
            return 0;
        }

        if (!$this->isValid(null)) {
            return null;
        }

        if (!$this->_valueDisabled && !$this->receive()) {
            return null;
        }

        return $this->_storedFileId;
    }
//------------------------------------------------------------------------------
	/**
	 * переустановка декоратора, хранящего id сохраненного файла
	 * @param int $value - идентификатор файла
	 * @return bool | Zend_Form_Decorator_Abstract
	 */
	protected function _resetStoredFileDecorator($value)
	{
		if(!$value)
			return false;
		$this->_storedFileId = (int) $value;
		$decorator = $this->getDecorator('Z_Admin_Form_Decorator_StoredFile');
		if($decorator)
			$decorator->setOptions(array('name' => $this->_storedFileDecName, 'value'=> $this->_storedFileId));
		return $decorator;
	}
//------------------------------------------------------------------------------
	/**
	 * Удаление файла, id которого хранится в декораторе
	 * @param <type> $id
	 * @return <type>
	 */
	protected function  _deleteStoredFile()
	{
		$storage = new Z_File_Storage();
		return $storage->removeFileDir($this->_storedFileId);
	}
//------------------------------------------------------------------------------
	/**
	 * Обработка поступающих на форму контекстных данных из _POST.
	 * Занимается установкой декоратора Z_Admin_Form_Decorator_StoredFile
	 * и обработкой удаления файла
	 * @param array $context
	 * @return null
	 */
	protected function _processStoredFile($context)
	{	
		if(!isset($context[$this->_storedFileDecName]))
			return;
		$decorator = $this->_resetStoredFileDecorator($context[$this->_storedFileDecName]);
		if(!$decorator)
			return;
		$checkedName = $decorator->getCheckedName();
		if(isset($context[$checkedName]))
		{
			$decorator->clearSelf();
			$this->_deleteStoredFile();
			$this->_storedFileId = null;
		}
	}

}
