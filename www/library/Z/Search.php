<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Лицензия
 *
 * Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации
 * (в дальнейшем именуемыми "Программное Обеспечение"),безвозмездно использовать Программное Обеспечение без ограничений,
 * включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам,
 * которым предоставляется данное Программное Обеспечение, соблюдении следующих условий:
 *
 * Вышеупомянутый копирайт и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
 *
 * При копировании, добавлении, изменении, распространении, продаже, публикации и сублицензировании программного обеспечения,
 * авторство может быть только дополнено, но не удалено или изменено на другое.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ,
 * ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И
 * ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА,
 * УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ
 * ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 *
 */

class Z_Search
{

    protected static $_instance = NULL;
    public static $spaceChars = array('~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '+', '-', '"', '\'', '№', ';', ':', '?', '*', '(', ')', '\\', '|', '/', '[', ']', '{', '}', ',', '.', '<', '>');
    protected static $stopWords = array('and', 'or');

    protected function __construct()
    {
    }

    /**
     *
     * @return Zend_Search_Lucene_Interface
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            $indexDir = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'lucene';
            //            $stopWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords(self::$stopWords);
            $analyzer = new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive();
            //            $analyzer->addFilter($stopWordsFilter);
            Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
            Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
            try
            {
                $index = Zend_Search_Lucene::open($indexDir);
            }
            catch (Exception $e)
            {
                $index = Zend_Search_Lucene::create($indexDir);
            }
            self::$_instance = $index;
        }
        return self::$_instance;
    }


    public static function buildQueryString($searchString)
    {
        $searchString = strip_tags($searchString);
        $searchString = trim($searchString, implode('', self::$spaceChars));
        $searchString = str_replace(self::$spaceChars, ' ', $searchString);
        $searchString = str_ireplace(self::$stopWords, '', $searchString);

        $searchStringBeforeCutWords = $searchString;
        $searchString = preg_replace('~(\s|^)[^\s]{1,3}\s~iu', ' ', $searchString);
        $searchString = preg_replace('~(\s|^)[^\s]{1,3}$~iu', ' ', $searchString);
        $searchString = trim($searchString);
        $searchString = preg_replace('~\s+~iu', '~ ', $searchString);
        if (!$searchString) $searchString = trim($searchStringBeforeCutWords);
        $searchString .= '~';
        return $searchString;
    }

    /**
     * @static
     * @param $searchString
     * @return Zend_Search_Lucene_Search_Query
     */
    public static function buildQuery($searchString)
    {
        return $query = Zend_Search_Lucene_Search_QueryParser::parse(self::buildQueryString($searchString));
    }

    /**
     * @static
     * @param $searchString
     * @return array
     */
    public static function find($searchString)
    {
        return self::getInstance()->find(self::buildQuery($searchString));
    }

}
