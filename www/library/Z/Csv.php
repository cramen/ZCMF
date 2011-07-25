<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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
class Z_Csv {

        public static function fgetcsv($f, $length, $d=",", $q='"') {
                $list = array();
                $st = fgets($f, $length);
                if ($st === false || $st === null) return $st;
                if (trim($st) === "") return array("");
                while ($st !== "" && $st !== false) {
                        if ($st[0] !== $q) {
                                # Non-quoted.
                                list ($field) = explode($d, $st, 2);
                                $st = substr($st, strlen($field)+strlen($d));
                        } else {
                                # Quoted field.
                                $st = substr($st, 1);
                                $field = "";
                                while (1) {
                                        # Find until finishing quote (EXCLUDING) or eol (including)
                                        preg_match("/^((?:[^$q]+|$q$q)*)/sx", $st, $p);
                                        $part = $p[1];
                                        $partlen = strlen($part);
                                        $st = substr($st, strlen($p[0]));
                                        $field .= str_replace($q.$q, $q, $part);
                                        if (strlen($st) && $st[0] === $q) {
                                                # Found finishing quote.
                                                list ($dummy) = explode($d, $st, 2);
                                                $st = substr($st, strlen($dummy)+strlen($d));
                                                break;
                                        } else {
                                                # No finishing quote - newline.
                                                $st = fgets($f, $length);
                                        }
                                }

                        }
                        $list[] = $field;
                }
		unset($st);
		unset($list);
                return $list;
        }

        public static function fputcsv($f, $list, $d=",", $q='"') {
                $line = "";
                foreach ($list as $field) {
                        # remove any windows new lines,
                        # as they interfere with the parsing at the other end
                        $field = str_replace("\r\n", "\n", $field);
                        # if a deliminator char, a double quote char or a newline
                        # are in the field, add quotes
                        if(ereg("[$d$q\n\r]", $field)) {
                                $field = $q.str_replace($q, $q.$q, $field).$q;
                        }
                        $line .= $field.$d;
                }
                # strip the last deliminator
                $line = substr($line, 0, -1);
                # add the newline
                $line .= "\n";
                # we don't care if the file pointer is invalid,
                # let fputs take care of it
                return fputs($f, $line);
        }
}