<?php

class Z_Statpage
{

  /**
   *
   * @var Z_Db_Table
   */
  protected static $_model = NULL;
//  protected static $_rows = NULL;
  protected $_isError = false;

  /**
   * принимает первый аргумент - идентификатор статьи или ее id в таблице
   * @var Zend_Db_Table_Row
   */
  protected $_row = NULL;

  public function __construct($sid)
  {
    self::$_model = new Z_Model_Statpage();
    $cache = Z_Cache::getInstance();
    if (!$this->_row = $cache->load('z_spatpage_'.$sid))
    {
      $this->_row = $this->_getRow($sid);
      $cache->save($this->_row,'z_spatpage_'.$sid);
    }
  }

  /**
   * @return string
   */
  public function getText()
  {
    return $this->_row->text;
  }

  /**
   * @return string
   */
  public function getTitle()
  {
    return $this->_row->title;
  }

  /**
   * @return string
   */
  public function get($field,$default=NULL)
  {
    $data = $this->_row->toArray();
    if (array_key_exists($field,$data))
      return $data[$field];
    else
      return $default;
  }

  /**
   * @return Zend_Db_Table_Row
   */
  protected function _getRow($sid)
  {
    $row = self::$_model->fetchRow(array('sid=?'=>$sid));
    if (!$row && is_numeric($sid))
    {
      $row = self::$_model->fetchRow(array('id=?'=>$sid));
    }
    if (!$row)
    {
      $this->_isError = true;
      $row = $this->_getErrorRow();
    }
    return $row;
  }


  /**
   * @return Zend_Db_Table_Row
   */
  protected function _getErrorRow()
  {
    $row = self::$_model->fetchRow(array('sid=?'=>'error'));
    if (!$row)
    {
      $configError = new Z_Config('error_text');
      $errtext = $configError->getValue();
      $row = self::$_model->createRow(array(
	      'sid'		=>	'error',
	      'title'		=>	'Ошибка',
	      'text'		=>	$errtext?$errtext:'Страница не найдена'
      ));
    }
    return $row;
  }

  public function isError()
  {
    return $this->_isError;
  }

  public function __toString()
  {
    return $this->getText();
  }

  public static function create($sid,$title,$content,$options = array())
  {
    $model = new Z_Model_Statpage();
    if ($model->fetchRow(array('sid=?'=>$sid))) throw new Exception('Страница с идентификатором "'.$sid.'" уже существует.');
    $model->createRow(array_merge($options,array(
      'sid'   =>  $sid,
      'title' =>  $title,
      'text'  =>  $content
    )));
  }

}

?>