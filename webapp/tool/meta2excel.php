#!/usr/bin/php
<?php
/**
 * make database definition excel document by meta.xml
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
 * meta.xml を読み込んでデータベース定義書の Excel を生成する
 *
 * [使い方]
 * meta2excel.php [meta.xmlファイルパス|all]
 *
 * [動作]
 * 1. 引数なし
 *     modules 内にあるモジュールの meta.xml を検出し、モジュール毎にExcelを生成
 * 2. all
 *     modules 内にあるモジュールの meta.xml を検出し、DbDefinition.xlsx として生成
 * 3. meta.xml 指定
 *     指定した meta.xml のモジュールのみ Excel を生成
 */

require_once "MetaProcessor.php";

if($argc > 1 && isset($argv[1]) && $argv[1] == "all"){
    $target_all = true;
} else {
    $target_all = false;
}

// 引数は一つもしくは null を想定
// 引数が all または null なら全ての meta.xml を読み込む
// 引数が all 以外の meta.xml なら指定の meta.xml を読み込む
if($argc > 1 && $target_all == false){
    if(!file_exists($argv[1])){
        print "Error: ".$argv[1]." is not found.\n";
        exit;
    }
    $metafiles = array($argv[1]);
} else {
    $metafiles = glob("../modules/*/sql/meta.xml");
}

$m = new MetaProcessor();
if($target_all){
    $m->countTable($metafiles);
}
foreach($metafiles as $metafile){
    if(!$target_all){
        $m->countTable(array($metafile));
    }
    $m->load($metafile);
    $m->parseMod();
    $m->parseDb();
    $m->parseTableCsv();
    $m->parseRecipeDef();
    $m->parseConfigDef();
//print_r($m->mod());
//print_r($m->tables());exit;
//print_r($m->recipe());
//print_r($m->db());exit;
    $m->createExcelObject();
    $m->generateDbDefinitionExcel();
    if($target_all == false){
        $m->generateToc();
        $m->writeExcel();
    }
}

if($target_all){
    // null または false を渡すと DbDefinition_モジュール名.xlsx で保存後、Excelオブジェクトをクリア
    // true を引数で渡すと DbDefinition.xlsx で保存
    $m->generateToc();
    $m->writeExcel($target_all);
}
//print_r($m->sheetList());
exit;
