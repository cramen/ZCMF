<?php

class Z_View_Template {
	
	protected $_template = "";
	protected $_values = array();
	
	
	function __construct($template="",$values=array())
	{
		$this->setTemplate($template);
		$this->setValues($values);
	}
	
	/**
	 * 
	 * @param unknown_type $template
	 * @return Z_View_Template
	 */
	public function setTemplate($template)
	{
		$this->_template = $template;
		return $this;
	}
	
	/**
	 * 
	 * @param unknown_type $values
	 * @return Z_View_Template
	 */
	public function setValues($values)
	{
		if ($values instanceof Zend_Config || $values instanceof Zend_Db_Table_Row || $values instanceof Zend_Db_Table_Rowset)
		$values = $values->toArray();
		$this->_values = $values;
		return $this;
	}

	/**
	 * 
	 * @param unknown_type $key
	 * @param unknown_type $value
	 * @return Z_View_Template
	 */
	public function setValue($key,$value)
	{
		$this->_values[$key] = $value;
		return $this;
	}
	
	/**
	 * 
	 * @param unknown_type $template
	 * @param unknown_type $values
	 * @param unknown_type $addslashes
	 * @return string
	 */
	protected function parse($template, $values, $addslashes)
	{
		preg_match_all('/\{\{(.*?)\}\}/si', $template, $res);
		if (@$res[1]) {
			foreach ($res[1] as $el)
			{
				if (isset($values[$el]))
					$template = str_ireplace('{{'.$el.'}}', ($addslashes ? addslashes(stripslashes(@$values[$el])) : @$values[$el]), $template);
			}
		}
		return $template;
	}
	
	/**
	 * @return string
	 */
	public function render()
	{
		$template = $this->parse($this->_template, $this->_values, false);
		return $template;
	}
	
	
	
}

?>