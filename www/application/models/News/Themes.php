<?
class Site_Model_News_Themes extends Z_Db_Table
{

    protected $_name = 'news_themes';

    protected static $pairs = null;

    public static function getPairs($pair = array('id','title'))
    {
        if (self::$pairs === null)
        {
            $model = new self();
            self::$pairs = $model->fetchPairs($pair,null,'orderid');
        }
        return self::$pairs;
    }

}
