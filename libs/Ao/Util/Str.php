<?php
/**
 * String Utility
 *
 * PHP 5
 *
 * Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright     Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
 * @link          http://bmath.jp Bmath Web Application Platform Project
 * @package       Ao.Util
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Ao_Util_Str {
    public function toPascal($str = null, $delim = "_")
    {
        $str  = strtolower($str);
        $strs = explode($delim, $str);
        $strs = array_map("ucfirst", $strs);
        return implode("", $strs);
    }
    public function toCamel($str = null, $delim = "_")
    {
        $str  = strtolower($str);
        $strs = explode($delim, $str);
        $strs = array_map("ucfirst", $strs);
        $strs[0] = strtolower($strs[0]);
        return implode("", $strs);
    }
    public function toSnake($str = null, $delim = "_")
    {
        $str = preg_replace("/([A-Z])/", "_$1", $str);
        $str = strtolower($str);
        return ltrim($str, "_");
    }

    // Zend_Db_Adapter_Abstruct::_quote() の先頭と末尾の "'" を付けないバージョン
    public function quote($value)
    {
        if (is_int($value)) { 
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        } 
        return addcslashes($value, "\000\n\r\\'\"\032");
    }

    // print_r の結果の文字列を配列変換する
    // -> 参照元：http://blog.asial.co.jp/407
    public function unprint_r($str, $num=0)
    {   
        $data_list = array();
        $str_list = explode("\n", $str);
        $add_list = array();
        $indent = ' {' . ($num*8 + 4) . '}';
        $pattern = '/^' . $indent . '\[.+\] => .*$/';
        $flag = false;
        foreach ($str_list as $value) { 
            if (preg_match('/^' .' {' . ($num*8) . '}' . '(Array|\()$/', $value)) {
                continue;
            }   
            if (preg_match('/^' .' {' . ($num*8) . '}' . '\)$/', $value)) {
                break;
            }   
            if (preg_match($pattern, $value, $matches)) {
                $flag = true;
                if (count($add_list)) {
                    $data_list[] = join("\n", $add_list);
                }   
                $add_list = array();
                $add_list[] = $value;
            } else {
                if ($flag) {
                    $add_list[] = $value;
                }
            }
        }
        $data_list[] = join("\n", $add_list);
        
        $result_list = array();
        foreach ($data_list as $data) {
            $pattern = '/'. $indent . '\[(.+?)\] => (.*)/s';
            preg_match($pattern, $data, $matches);
            $key   = $matches[1];
            $value = $matches[2];
            if (strstr($value, 'Array')) {
              $result_list[$key] = unprint_r($data, $num+1);
            } else {
              $result_list[$key] = $value;
            }
        }
        return $result_list;
    }

    public static function toUtf8($val = array()){
        $count = count($val);
        for($i = 0; $i < $count; $i++ ){
            $val[$i] = mb_convert_encoding($val[$i], "UTF-8", 'sjis-win');
        }
        return $val;
    }

    public static function obGetContents($var, $use_var_dump = false)
    {
        ob_start();
        if ($use_var_dump) {
            var_dump($var);
        }
        else {
            print_r($var);
        }
        $content = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
}
