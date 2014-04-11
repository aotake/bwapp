<?php
/**
 * Utility
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
 * @package       Ao
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Ao_Util {
    static public function dump($var = null, $var_dump = false, $exit = true)
    {
        print "<pre>";
        if($var_dump){
            var_dump($var);
        } else {
            print_r($var);
        }
        print "</pre>";

        if($exit){
            exit;
        }
    }
    static public function isSsl()
    {
        return (false===empty($_SERVER['HTTPS'])) && ('off'!==$_SERVER['HTTPS']);
    }
    static public function prepareUrl($str, $act, $ctr, $mod = "default", $params = null)
    {
        $root_url = Zend_Registry::get("config")->site->root_url;
        if($mod == "default"){
            if($act == "index" && $ctr == "index"){
                $url = $root_url."/";
            } else if($act == "index" && $ctr != "index"){
                $url = $root_url."/$ctr/";
            } else {
                $url = $root_url."/$ctr/$act/";
            }
        } else {
            if($act == "index" && $ctr == "index" ){
                $url = $root_url."/$mod/";
            } else if($act == "index" && $ctr != "index") {
                $url = $root_url."/$mod/$ctr/";
            } else {
                $url = $root_url."/$mod/$ctr/$act/";
            }
        }
        $tag = "<a href='".$url."'>".$str."</a>";
        return $tag;
    }
}
