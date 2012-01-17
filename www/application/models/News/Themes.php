<?
class Site_Model_News_Themes extends Z_Db_Table
{

    protected $_name = 'news_themes';

    protected static $pairs = null;

    public static function getPairs()
    {
        if (self::$pairs === null)
        {
            $model = new self();
            self::$pairs = $model->fetchPairs(array('id','title'),null,'orderid');
        }
        return self::$pairs;
    }

}
