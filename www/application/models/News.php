<?
class Site_Model_News extends Z_Db_Table
{

    protected $_name = 'news';

    public function ZGetLinks($count = 0)
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();

        $newsurl = $router->assemble(array(),'news');

        $res = array(
            $newsurl => 'Все новости'
        );

        $modelThemes = new Site_Model_News_Themes();
        foreach ($modelThemes->fetchAll(null,'orderid') as $theme)
        {
            $res[$router->assemble(array('theme'=>$theme->id),'news')] = 'Новости ('.$theme->title.')';
        }

        return $res;
    }

}
