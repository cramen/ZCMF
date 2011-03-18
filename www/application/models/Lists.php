<?
class Site_Model_Lists extends Z_Db_Table
{

    protected $_name = 'lists';

	public function ZGetLinks($count=0)
	{
		$router = Zend_Controller_Front::getInstance()->getRouter();
		
		$select = $this->select()
			->from($this,array('id','title'))
			->order('title');
		if ($count) $select->limit($count);
		$result = $this->getAdapter()->fetchPairs($select);
		
		$res = array();
		foreach ($result as $key=>$val)
		{
			$res[$router->assemble(array('group'=>$key),'listgroup',true)] = $val;
		}
						
		return $res;
	}
    
}
