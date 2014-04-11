<?php
/**
 * Time String Utility
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

class Ao_Util_Time
{
    const pat1 = "/^(\d+)分(\d+)秒$/";
    const pat2 = "/^(\d+)分$/";
    const pat3 = "/^(\d+)秒$/";
    const pat4 = "/^(\d+):(\d+):(\d+)$/";
    const pat5 = "/^(\d+):(\d+)$/";
    const pat6 = "/^(\d+)$/";
    const pat7 = "/^(\d+)’(\d+)$/";

    // xx時xx分を秒数（Int)に変換する
    static public function convTimeStrToSec($time_str)
    {
        $time_str = trim($time_str);
        $time = 0;
        if(preg_match(self::pat1, $time_str, $match)){
            $time += (int)$match[1] * 60 + (int)$match[2];
        }
        else if(preg_match(self::pat2, $time_str, $match)){
            $time += (int)$match[1] * 60;
        }
        else if(preg_match(self::pat3, $time_str, $match)){
            $time += (int)$match[1];
        }
        else if(preg_match(self::pat4, $time_str, $match)){
            $time += (int)$match[1] * 60 * 60 + (int)$match[2] * 60 + (int)$match[3];
        }
        else if(preg_match(self::pat5, $time_str, $match)){
            $time += (int)$match[1] * 60 + (int)$match[2];
        }
        else if(preg_match(self::pat6, $time_str, $match)){
            $time += (int)$match[1];
        }
        else if(preg_match(self::pat7, $time_str, $match)){
            $time += (int)$match[1] * 60 + (int)$match[2];
        }
        return $time;
    }
    static public function convSecToTimeStr($sec, $type = 1, $zeropadding = false)
    {
        $div = $sec / 60;
        if($zeropadding){
            $sec = sprintf("%02d", $sec % 60);
            $min = sprintf("%02d", $div % 60);
            $hour= sprintf("%02d", $div / 60);
        } else {
            $sec = $sec % 60;
            $min = $div % 60;
            $hour= $div / 60;
        }
        switch($type){
        case 1: $str = $min."分".$sec."秒"; break;
        case 2: $str = $min."分"; break;
        case 3: $str = $sec."秒"; break;
        case 4: $str = $hour.":".$min.":".$sec; break;
        case 5: $str = $min.":".$sec; break;
        case 6: $str = $sec; break;
        default: $str = $min."分".$sec."秒"; break;
        }
        return $str;
    }
    static public function convSecTo($type = "h", $_sec)
    {
        $div = floor($_sec / 60); // init for min, hour
        $sec = $_sec % 60;
        $min = $div % 60;
        $hour= floor($div / 60);

        if($type == "h" || $type == "H"){
            return $hour;
        }
        else if($type == "m" || $type == "i" || $type == "I"){
            return $min;
        }
        else if($type == "s"){
            return $sec;
        }
        else{
            return $_sec;
        }
    }
}
