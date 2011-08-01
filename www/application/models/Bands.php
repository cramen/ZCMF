<?
class Site_Model_Bands extends Z_Db_Table
{

    protected $_name = 'bands';

    public function ZGetLinks($count=0)
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();

        $select = $this->select()
            ->from($this,array('band_sid','title'))
            ->order('orderid asc');

        if ($count) $select->limit($count);

        $result = $this->getAdapter()->fetchPairs($select);

        $res = array();

        foreach ($result as $key=>$val)
        {
            $res[$router->assemble(array('bandsid'=>$key),'band',true)] = $val;
        }

        return $res;
    }

}
