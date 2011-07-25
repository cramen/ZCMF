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
    $cache_id = 'z_spatpage_'.str_replace(array(DIRECTORY_SEPARATOR,'.','-',':','/','\\'),'_',$sid);
    if (!$this->_row = $cache->load($cache_id))
    {
      $this->_row = $this->_getRow($sid);
      $cache->save($this->_row,$cache_id);
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