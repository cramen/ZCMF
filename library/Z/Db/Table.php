<?php

require_once 'Zend/Db/Table/Abstract.php';

class Z_Db_Table extends Zend_Db_Table_Abstract
{

  /**
   * Возвращает ассоцитиативный массив, где ключем является поле, указанное в первом элементе параметра $keys а значение во второом
   * @param <array('id','title')> $keys
   * @param <array> $where
   * @param <array or string> $order
   * @return <array>
   */
  public function fetchPairs($keys = NULL,$where=NULL,$order=NULL)
  {
    if ($keys===NULL) $keys = array('id','title');
    if ($where===NULL) $where = array();
    $select = $this->select()->from($this,$keys);
    if (!empty($where))
    {
      foreach ($where as $key=>$value)
      {
	$select->where($key,$value);
      }
    }
    if (!empty($order)) $select->order($order);
    $ret = $this->getAdapter()->fetchPairs($select);
    return $ret;
  }

  /**
   * Возвращяет элементы с заданными id из входного массива в порядке следования их во входном массиве
   * @param <array> $ids
   * @return Zend_Db_Table_Rowset
   */
  public function fetchByIds($ids = array(),$where=array(),$order=NULL,$count=NULL,$offset=NULL)
  {
    return $this->fetchAll($this->fetchByIdsSelect($ids,$where,$order,$count,$offset));
  }

  /**
   * 
   * @return Zend_Db_Table_Select
   */
  public function fetchByIdsSelect($ids = array(),$where=array(),$order=NULL,$count=NULL,$offset=NULL)
  {
  	if (!$where) $where=array();
    $id_list = implode(',', $ids);
    if (!empty($ids))
    {
	    $where['goods.id IN ('.$id_list.')'] = '';
	    $order = $order?$order:'FIELD('.$this->info('name').'.id, '.$id_list.')';
    }
    else 
    {
    	$where['false'] = '';
    }
    $select = $this->select(true);
    foreach ($where as $key=>$where_item)
    	$select->where($key,$where_item);
    $select->order($order);
    $select->limit($count,$offset);
    return $select;
  }
  
}
