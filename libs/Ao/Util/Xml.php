<?php
/**
 * Xml Utility
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

class Ao_Util_Xml
{
    private $obj;
    private $obj_load;

    public function __construct()
    {
        $this->obj_load = false;
        $this->obj = null;
    }
    public function load($filepath)
    {
        if(!file_exists($filepath)){
            throw new Zend_Exception("not found: $filepath");
        }
        $this->obj = simplexml_load_file($filepath,NULL,LIBXML_NOCDATA);
        return $this;
    }
    public function parseString($string)
    {
        if(!$string){
            throw new Zend_Exception("not xml string: $string");
        }
        $this->obj = simplexml_load_string($string,NULL,LIBXML_NOCDATA);
        return $this;
    }
    // see: http://soft.fpso.jp/develop/php/entry_2764.html
    public function toArray($xmlobj = null)
    {
        if($xmlobj == "" && $this->obj && $this->obj_load == false){
            // 一番最初だけ simplexml_load_file() の結果を読み込む
            $xmlobj = $this->obj;
            $this->obj_load = true;
        }
        else if( empty($xmlobj) && $this->obj == "" ){
            // 註）empty($xmlobj) だけだとノードの値が NULL の時にエラーとなってしまうので
            //     $this->obj があるかをチェックする
            //var_dump($this->obj);
            throw new Zend_Exception("no xml object");
        }
        $arr = array();
        if (is_object($xmlobj)) {
            $xmlobj = get_object_vars($xmlobj);
        } else {
            $xmlobj = $xmlobj;
        }
 
        foreach ($xmlobj as $key => $val) {
            if (is_object($xmlobj[$key])) {
                $arr[$key] = self::toArray($val);
            } else if (is_array($val)) {
                foreach($val as $k => $v) {
                    if (is_object($v) || is_array($v)) {
                        $arr[$key][$k] = self::toArray($v);
                    } else {
                        $arr[$key][$k] = $v;
                    }
                }
            } else {
                $arr[$key] = $val;
            }
        }
        return $arr;
    }
}
