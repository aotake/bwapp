<?php
/**
 * Generate code for Form class
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
 * @package       Ao.webapp.tool
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author        aotake (aotake@bmath.org)
 */
/*
 * Form クラスのコード生成スクリプト
 *
 * 書式をよく忘れるので XML からコード生成する
 */

if(phpversion() >= "5.1.0"){
    date_default_timezone_set("Asia/Tokyo");
}
require_once "CodeTool.php";


$file = $argv[1];
$codetool = new CodeTool();
$codetool->setupEnv();
$target_section = $codetool->getApplicationEnv();
$config_file = "../config/config.ini";
if(file_exists("../config/custom/config.ini")){
    $config_file = "../config/custom/config.ini";
}
$codetool->loadConfig($config_file, $target_section);
$xml_array = $codetool->loadXml($file)->toArray();
// xml_array を正規化
if(!isset($xml_array["model"]["item"][0])){
    $tmp = $xml_array["model"]["item"];
/*
    // ソート条件を分割して配列化　
    if(
        isset($tmp["array_source"]) 
        && isset($tmp["array_source"]["sort"]) 
    ){
        $sort = explode(",", $tmp["array_source"]["sort"]);
        $sort = array_map("trim", $sort);
        $tmp["array_source"]["sort"] = $sort;
    }
    // デフォルトのオプション値設定を配列化
    if(
        isset($tmp["array_source"]) 
        && isset($tmp["array_source"]["default_option"]) 
        && !isset($tmp["array_source"]["default_option"]["option"][0])
    ){
        $tmp_opt = $tmp["array_source"]["default_option"]["option"];
        $tmp["array_source"]["default_option"]["option"] = array($tmp_opt);
    }
*/
    $xml_array["model"]["item"] = array($tmp);
}
//print_r($xml_array);exit;

foreach($xml_array["model"]["item"] as $i => $tmp){
    //$tmp = $xml_array["model"]["item"];
    // ソート条件を分割して配列化　
    if(
        isset($tmp["array_source"]) 
        && isset($tmp["array_source"]["sort"]) 
    ){
        $sort = explode(",", $tmp["array_source"]["sort"]);
        $sort = array_map("trim", $sort);
        $tmp["array_source"]["sort"] = $sort;
    }
    // デフォルトのオプション値設定を配列化
    if(
        isset($tmp["array_source"]) 
        && isset($tmp["array_source"]["default_option"]) 
        && !isset($tmp["array_source"]["default_option"]["option"][0])
    ){
        $tmp_opt = $tmp["array_source"]["default_option"]["option"];
        $tmp["array_source"]["default_option"]["option"] = array($tmp_opt);
    }
    $xml_array["model"]["item"][$i] = $tmp;
}

$ModuleName = Ao_Util_Str::toPascal($xml_array["module"]);
$ModelName = $xml_array["model"]["name"];

$output_file = "libs/".$ModuleName."/Form/".$ModelName.".php";

$codes = array();
foreach($xml_array["model"]["item"] as $item){
    $element = $item["element"];
    $s = $codetool->initSmarty();
    $s->assign("ModuleName", $ModuleName);
    $s->assign("NamePascal", Ao_Util_Str::toPascal($item["name"]));

    $s->assign("item", $item);
    switch($element){
    case "radio":
    case "checkbox":
    case "text":
    case "textarea":
    case "select":
        $tpl = "formcode/select.php";
        break;
    default:
        break;
    }
    $code = $s->fetch($tpl);
    $codes[$item["name"]] = preg_replace("/^<\?php\n/", "", $code);
}

$s = $codetool->initSmarty();
$s->assign("ModuleName", $ModuleName);
$s->assign("ModelName", $ModelName);
$s->assign("codes", $codes);
$tpl = "formcode/Skelton.php";
print $s->fetch($tpl);
