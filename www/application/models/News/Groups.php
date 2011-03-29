<?
class Site_Model_News_Groups extends Z_Db_Table
{

    protected $_name = 'news_groups';

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
			$res[$router->assemble(array('group'=>$key),'newsgroup',true)] = $val;
		}
						
		return $res;
	}

}
