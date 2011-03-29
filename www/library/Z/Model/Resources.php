<?php

require_once 'Z/Db/Table.php';

class Z_Model_Resources extends Z_Db_Table
{
    protected $_name = 'z_resources';
    protected static $_resources = NULL;

    /**
     * @return string
     */
//    public function getResourceId($id)
//    {
//    	if (NULL===self::$_resources)
//    	{
//    		$select = $this->select(true)->reset(Zend_Db_Select::COLUMNS)->columns(array('id','resourceId'));
//    		self::$_resources = $this->getAdapter()->fetchPairs($select);
//    	}
//    	
//        $id = (int) $id;
//
//        if (array_key_exists($id,self::$_resources))
//        {
//            return self::$_resources[$id];
//        }
//        else
//        {
//            return null;
//        }
//    }
    
    public function fetchPairsCat($parentid=0,$level=0)
    {
    	$where = array();
    	$where['parentid=?'] = $parentid;
    	$items = $this->fetchPairs(array('id','title'),$where,'orderid');
    	$retitems = array();
    	$pref = str_repeat('--',$level);
//    	$level++;
    	foreach ($items as $key=>$value)
    	{
    		$retitems[$key] = $pref.$value;
    		if ($additems = $this->fetchPairsCat($key,$level+1))
    		{
    			$retitems+=$additems;
    		}
    	}
    	return $retitems;
    }
}