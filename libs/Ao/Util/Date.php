<?php
/**
 * Date Format Utility
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
class Ao_Util_Date
{

    const pat1 = '/^(\d{4})-(\d{1,2})-(\d{1,2})$/';
    const pat2 = '/^(\d{4})-(\d{1,2})-(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})$/';

    // 日付形式か
    static public function isDateFormat($str)
    {
        return preg_match(self::pat1, $str) 
            || preg_match(self::pat2, $str) ;
    }
    // 日付形式をUNIX_TIMESTAMP表現に変換する
    static public function convYmdToInt($ymd)
    {
        if(preg_match(self::pat1, $ymd, $match)){
            $y = (int)$match[1];
            $m = (int)$match[2];
            $d = (int)$match[3];
            $h = $i = $s = 0;
        }
        else if(preg_match(self::pat2, $ymd, $match)){
            $y = (int)$match[1];
            $m = (int)$match[2];
            $d = (int)$match[3];
            $h = (int)$match[4];
            $i = (int)$match[5];
            $s = (int)$match[6];
        }
        else {
            return $ymd;
        }
        // 日付が正しく指定されていなければ、時刻が選択されていても 0 とする
        $int = null;
        if($y == "--" || $m == "--" || $d == "--"){
            $int = 0;
        } else {
            $int = mktime($h, $i, $s, $m, $d, $y);
        }
        return $int;
    }
    static public function convIntToYmd($int, $format = "Y-m-d")
    {
        return date($format, $int);
    }

}
