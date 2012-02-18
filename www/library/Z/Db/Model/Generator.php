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

class Z_Db_Model_Generator
{

    protected static $_classPrefix = 'Site_Model_';
    protected static $_classPrefixZ = 'Z_Model_';
    protected static $_extendedClass = 'Z_Db_Table';

    /**
     * Генерирует класс модели
     * @param string $className
     * Название класса (без префикса)
     * @param string $tableName
     * Название таблицы в БД
     * @param array $params
     * Параметры для переопределения настроек по умолчанию
     * @return string
     */
    public static function generate($className, $tableName = NULL, $params = array())
    {

        if (strpos($className, 'z_') === 0 && Z_Auth::getInstance()->getUser()->getRole() == 'root') {
            $path_prefix = APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'Z' . DIRECTORY_SEPARATOR . 'Model';
            self::$_classPrefix = isset($params['prefixz']) ? $params['prefixz'] : self::$_classPrefixZ;
        }
        else
        {
            $path_prefix = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'models';
            self::$_classPrefix = isset($params['prefix']) ? $params['prefix'] : self::$_classPrefix;
        }


        if ($tableName == NULL) $tableName = strtolower($className);
        $className = explode('_', $className);
        $className = array_map('ucfirst', $className);

        $path = $className;
        unset($path[count($path) - 1]);
        $path = implode(DIRECTORY_SEPARATOR, $path);

        $filename = $className[count($className) - 1] . '.php';
        $className = implode('_', $className);

        $filepath = $path_prefix . DIRECTORY_SEPARATOR . $path;

        $generator = new Zend_CodeGenerator_Php_Class();
        $generator->setName(self::$_classPrefix . $className)
                ->setExtendedClass(self::$_extendedClass)
                ->setProperty(array(
            'name' => '_name',
            'visibility' => 'protected',
            'defaultValue' => $tableName,
        ));
        Z_Fs::create_file($filepath . DIRECTORY_SEPARATOR . $filename, "<?\n" . $generator->generate());

    }

    public static function getAllModels()
    {
        $ret = array();
        $path1 = APPLICATION_PATH . '/models';
        $ret = self::getFileListRecursive($path1);
        foreach ($ret as $key => $el)
        {
            $modelName = 'Site_Model_' . str_replace(array($path1 . '/', '.php', '/', '\\'), array('', '', '_', '_'), $el);
            if (class_exists($modelName)) {
                $ret[$key] = $modelName;
            }
            else
            {
                unset($ret[$key]);
            }
        }
        $path2 = APPLICATION_PATH . '/../library/Z/Model';
        $ret2 = self::getFileListRecursive($path2);
        foreach ($ret2 as $key => $el)
        {
            $modelName = 'Z_Model_' . str_replace(array($path2 . '/', '.php', '/'), array('', '', '_'), $el);
            if (class_exists($modelName)) {
                $ret2[$key] = $modelName;
            }
            else
            {
                unset($ret2[$key]);
            }
        }
        $ret = array_merge($ret, $ret2);
        sort($ret);

        return $ret;
    }

    private static function getFileListRecursive($path)
    {
        $ret = array();
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle)))
            {
                if ($file != '.' && $file != '..') {
                    if (is_file($path . '/' . $file)) {
                        $ret[] = $path . '/' . $file;
                    }
                    elseif (is_dir($path . '/' . $file))
                    {
                        $ret = array_merge($ret, self::getFileListRecursive($path . '/' . $file));
                    }
                }
            }
        }
        return $ret;
    }

}

?>