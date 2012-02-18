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

class Z_Text
{

    protected static $alpha_lower = array(
        'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я'
    );

    protected static $alpha_upper = array(
        'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'
    );


    /**
     * Приводит строку в верхний регистр
     * @param string $string
     */
    public static function strtoupper($string)
    {
        return str_replace(self::$alpha_lower, self::$alpha_upper, strtoupper($string));
    }

    /**
     * Приводит строку в верхний регистр
     * @param string $string
     */
    public static function strtolower($string)
    {
        return str_replace(self::$alpha_upper, self::$alpha_lower, strtolower($string));
    }

    /**
     * Режет строку на отдельные слова
     * @param string $string
     * @param array $whitespace
     * Массив пробельных символов
     * @return array
     */
    public static function explodetowords($string, $whitespace = Array('-', '_', '.', ',', ':', ';', '(', ')', '<', '>', '~', '+', '*', '"', "\\", '\'', '&', '^', '`'))
    {
        // Заменяем КАК БЫ пробельные символы на реальные пробелы
        $query_array = str_replace($whitespace, ' ', $string);
        // Заменяем двойные пробелы на одиночные
        while (strpos($query_array, '  ') !== false)
        {
            $query_array = str_replace(Array('  ', '   ', '    ', '     '), Array(' '), $query_array);
        }
        $arr = explode(' ', $query_array);
        return $arr;
    }

    /**
     * Заменяет символы HTML разметки функцией htmlSpecialChars
     * @param array or string $in
     */
    public static function htmlSpecialChars($in)
    {
        if (is_string($in)) {
            return htmlspecialchars($in);
        }
        elseif (is_array($in))
        {
            $ret = array();
            foreach ($in as $key => $el)
            {
                $ret[$key] = self::htmlSpecialChars($el);
            }
            return $ret;
        }
    }

    public static function csvStringToArray(&$string, $CSV_SEPARATOR = ';', $CSV_ENCLOSURE = '"', $CSV_LINEBREAK = "\n")
    {
        $o = array();

        $cnt = strlen($string);
        $esc = false;
        $escesc = false;
        $num = 0;
        $i = 0;
        while ($i < $cnt)
        {
            $s = $string[$i];

            if ($s == $CSV_LINEBREAK) {
                if ($esc) {
                    $o[$num] .= $s;
                } else
                {
                    $i++;
                    break;
                }
            } elseif ($s == $CSV_SEPARATOR)
            {
                if ($esc) {
                    $o[$num] .= $s;
                } else
                {
                    $num++;
                    $esc = false;
                    $escesc = false;
                }
            } elseif ($s == $CSV_ENCLOSURE)
            {
                if ($escesc) {
                    $o[$num] .= $CSV_ENCLOSURE;
                    $escesc = false;
                }

                if ($esc) {
                    $esc = false;
                    $escesc = true;
                } else
                {
                    $esc = true;
                    $escesc = false;
                }
            } else
            {
                if ($escesc) {
                    $o[$num] .= $CSV_ENCLOSURE;
                    $escesc = false;
                }

                if (!array_key_exists($num, $o)) $o[$num] = '';
                $o[$num] .= $s;
            }

            $i++;
        }

        //  $string = substr($string, $i);

        return $o;
    }


}

?>