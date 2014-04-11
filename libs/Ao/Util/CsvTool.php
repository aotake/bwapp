<?php
/**
 * Csv Tool
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

// for Mac
//    See: http://jp.php.net/manual/ja/ref.filesystem.php#ini.auto-detect-line-endings
//    http://jp.php.net/fgets
//        auto-detect-line-endings を true にすると Mac の改行コードを改行として
//        認識してくれるようになるらしい
ini_set("auto_detect_line_endings", true);

class Ao_Util_CsvTool
{

    var $csv_dir;
    var $filename;
    var $csvData;

    var $handle; // file pointer

    function CsvTool($csv_dir = '/tmp/csv', $filename = null){
        $this->csv_dir  = $csv_dir;
        $this->filename = $filename;
        $this->csvData  = array();
    }

    function setCsvDir($path = null){
        $this->csv_dir = $path;
    }

    function setCsvFilename( $filename ){
        if( !$filename ){
            print "invalid csv filename[$filename]";
            exit;
        }
        $this->filename = $filename;
    }

    function getCsvFilename(){
        return $this->csv_dir.'/'.$this->filename;
    }

    function csvExists(){
        return file_exists( $this->getCsvFilename() );
    }

    function open(){
        $this->handle = fopen($this->getCsvFilename(), "r");
    }
    function close(){
        fclose($this->handle);
    }
    function fetch(){
        return fgets($this->handle);
    }
    function splitLine($val)
    {
        $val = trim($val);
        $val = str_replace("\"","",$val);
        $rec = explode(",", $val);
        if( empty($rec) ) {
            $rec = false;
        }
        return $rec;
    }

    function load(){
        if( !file_exists( $this->getCsvFilename() ) ){
            print "CSV file Not found :". $this->getCsvFilename()."\n";
            exit;
        }
        /* fgetcsv は PHP5 で仕様が変わってトラブルが多いっぽいので佐藤方式で処理する */
        /* mb_string をつかわないで qkc で変換 */
        /* shell_exec("/var/www/html-music8.jp/admdbtool/qkc/qkc -u ".$this->getCsvFilename()); */
        $row = 1;
        $this->handle = fopen($this->getCsvFilename(), "r");
        //while (($rec = fgetcsv($handle, 20000, ",")) !== FALSE) {
        while (($val = fgets($this->handle)) !== FALSE) {
            $val = trim($val);
            $val = str_replace("\"","",$val);
            $rec = explode(",", $val);
            if( empty($rec) ) { continue; }
            $this->csvData[] = $rec;
        }
        fclose($this->handle);
    }

    function recordNum(){
        return count($this->csvData);
    }

    function fetchAll(){
        return $this->csvData;
    }
    function fetchRow($row){
        if( $row >= count( $this->csvData ) ){
            print "fetchRow(): argument value must be smaller than ".count($this->csvData).". --> arg = ".$row."\n";
            return false;
        }
        return $this->csvData[ $row ];
    }

    function toEuc($val = array()){
        $count = count($val);
        for($i = 0; $i < $count; $i++ ){
            $val[$i] = mb_convert_encoding($val[$i], "EUC", "SJIS");
        }
        return $val;
    }
    function toUtf8($val = array()){
        return Ao_Util_Str::toUtf8($val);
    }
}
