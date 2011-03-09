<?php
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