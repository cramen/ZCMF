<?
class Site_Model_Dummy
{

    public function ZGetLinks($count = 0)
    {
        return array(
            '/feedback' => 'Форма обратной связи'
        );
    }

    public function ZSitemapXml()
    {
        $res = array();
        $res[] = new Z_Sitemap_Xml_Url(array('controller' => 'feedback'), 'default');
        return $res;
    }

}
