<?
class Site_Model_Gallery extends Z_Db_Table
{

    protected $_name = 'gallery';

	public function ZGetLinks($count=0)
	{
		$router = Zend_Controller_Front::getInstance()->getRouter();
		
		$select = $this->select()
			->from($this,array('id','title'))
			->order('orderid asc');
		if ($count) $select->limit($count);
		$result = $this->getAdapter()->fetchPairs($select);
		
		$res = array();
		$res[$router->assemble(array('group'=>'0'),'gallerymain',true)] = 'Список галерей';
		foreach ($result as $key=>$val)
		{
			$res[$router->assemble(array('group'=>$key),'gallerygroup',true)] = $val;
		}
						
		return $res;
	}
	
}
