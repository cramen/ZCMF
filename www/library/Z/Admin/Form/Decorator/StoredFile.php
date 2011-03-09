<?php
class Z_Admin_Form_Decorator_StoredFile extends Zend_Form_Decorator_Abstract
{
    /**
     * Default placement: append
     * @var string
     */
    protected $_placement = 'APPEND';
    /**
     * 
     * @var Z_File_Storage_File
     */
    protected $_file=NULL;
//	protected $_filePath;
//	protected $_fileName;
//	protected $_fileSiteName;
//------------------------------------------------------------------------------
	/**
	 * get value of hidden field
	 * @return string
	 */
	public function getValue()
	{
		return $this->getOption('value');
	}
//------------------------------------------------------------------------------
	/**
	 * get name of hidden field
	 * @return string
	 */
	public function getName()
	{
		return $this->getOption('name');
	}
//------------------------------------------------------------------------------
	/**
	 * установка пути к фалу и имени файла по его id
	 * @param int $val
	 * @return bool
	 */
	protected function _setFile($val)
	{
		$storage = new Z_File_Storage();
		$file = $storage->getFile((int) $val);
		if(!$file)
		{
			unset($this->_options['value']);
			return false;
		}
		$this->_file = $file;
//		$this->_filePath = $file->getSitePath();
//		$this->_fileName = $file->getName();
//		$this->_fileSiteName = $file->getSiteName();
		return true;
	}
//------------------------------------------------------------------------------
	/**
	 * Установка имени для checkbox по умолчанию
	 * @param string $name
	 * @return null
	 */
	protected function _setCheckedNameByName($name)
	{
		if($this->getCheckedName() != null)
			return;
		$this->setOption('checkedName', $name . "_check");
	}
//------------------------------------------------------------------------------
    /**
     * Get class with which to define description
     *
     * Defaults to 'hint'
     *
     * @return string
     */
    public function getClass()
    {
        $class = $this->getOption('class');
        if (null === $class) {
            $class = 'hint';
            $this->setOption('class', $class);
        }
        return $class;
    }
//------------------------------------------------------------------------------
	/**
	 * get status of checkbox
	 * @return string
	 */
	public function getChecked()
	{
		return $this->getOption('checked');
	}
//------------------------------------------------------------------------------
	/**
	 * get name of checkbox
	 * @return string
	 */
	public function getCheckedName()
	{
		return $this->getOption('checkedName');
	}
//------------------------------------------------------------------------------
    /**
     * Set options
     *
     * @param  array $options
     * @return Zend_Form_Decorator_Abstract
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
		if(isset($options['value']))
			$this->_setFile($options['value']);
		if(isset($options['name']))
			$this->_setCheckedNameByName($options['name']);
        return $this;
    }
//------------------------------------------------------------------------------
    /**
     * Set option
     *
     * @param  string $key
     * @param  mixed $value
     * @return Zend_Form_Decorator_Abstract
     */
    public function setOption($key, $value)
    {
        $this->_options[(string) $key] = $value;
		if($key == 'value')
			$this->_setFile($value);
		if($key == 'name')
			$this->_setCheckedNameByName($value);
        return $this;
    }
//------------------------------------------------------------------------------
	public function clearSelf()
	{
		$this->setOptions(array("value" => null, "checkedName" => null));
	}
//------------------------------------------------------------------------------
    /**
     * Render a description
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
		$element = $this->getElement();
        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        $class     = $this->getClass();
		$value = $this->getValue();
		$name = $this->getName();
		$checked = $this->getChecked();
		$checkedName = $this->getCheckedName();
		$box = ($checkedName == null || $element->isRequired()) ? '' : "<input class=\"z-form-checkbox\" type = 'checkbox'  name = '$checkedName' $checked />Удалить<br />";
		$hfield = $value ? "$box
		<input type = 'hidden' value = '$value' name = '$name' />
			<a target=\"_blank\" href = '" . $this->_file->getSiteName() . "'>" . $this->_file->getName() . "</a>
		" : '';
		if ($hfield)
		$hfield = '<div>'.$hfield.'</div>';
		switch ($placement)
		{
            case self::PREPEND:
                return $hfield . $separator . $content;
            case self::APPEND:
            default:
                return $content . $separator . $hfield;
		}
	}
}
