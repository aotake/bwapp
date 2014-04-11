<?php
/**
 * Gengo Utility
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
class Ao_Util_Gengo {

    // 元号＋年（数字）を返す
    // 月日も指定すると元号＋年月日で返す
    public function toWareki($year = null, $month = null, $day = null)
    {
        $wareki = null;
        if($year == 1868){
            $wareki = "明治元";
        }
        else if(1868 < $year && $year < 1912){
            $year = $year - 1867;
            $wareki = "明治".$year;
        }
        else if($year == 1912){
            $year = $year - 1867;
            if($month == null){
                $wareki = "大正元";
            }
            else if($month < 7 || ($month == 7 && $day < 31)){
                $wareki = "明治".$year;
            }
            else {
                $wareki = "大正元";
            }
        }
        else if(1912 < $year && $year < 1926){
            $year = $year - 1911;
            $wareki = "大正".$year;
        }
        else if($year == 1926){
            $year = $year - 1911;
            if($month == null){
                $wareki = "昭和元";
            }
            else if($month < 12 || ($month == 12 && $day < 25)){
                $wareki = "大正".$year;
            }
            else{
                $wareki = "昭和元";
            }
        }
        else if(1926 < $year && $year < 1989){
            $year = $year - 1925;
            $wareki = "昭和".$year;
        }
        else if($year == 1989){
            $year = $year - 1925;
            if($month == null){
                $wareki = "平成元";
            }
            else if($month == 1 && $day < 7){
                $wareki = "昭和".$year;
            }
            else{
                $wareki = "平成元";
            }
        }
        else if(1988 < $year){
            $year = $year - 1988;
            $wareki = "平成".$year;
        }
        else{
            $wareki = "--";
        } 

        // 月日があれば付け足す
        if($month && $day){
            $wareki .="年".$month."月".$day."日";
        }
        return $wareki;
    }
}

