#!/usr/bin/php
<?php
/**
 * generate Sphinx document by meta.xml
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

/**
 * Sphinx ドキュメント生成＋コンパイル
 *
 * meta.xml を読み込んで sphinx ドキュメントを作成する
 *
 * modules/<modulename>/doc/sphinx ディレクトリに meta.xml の <sphinx_page> ノードの内容を 1 ファイルずつ保存する。
 * 保存後 make html を実行する。
 *
 * 【前提】上記 sphinx ディレクトリで sphinx-quickstart コマンドでプロジェクトを生成済みであること。
 * 【注意】もし sphinx のプロジェクトが未作成の場合は
 */

require_once "MetaProcessor.php";
if($argc < 2 && !file_exists("./.sphinx.php.last")){
    print "Usage: ".$argv[0]." <metafile_path>\n";
    exit;
}
else if($argc < 2 && file_exists("./.sphinx.php.last")){
    $argv[1] = file_get_contents("./.sphinx.php.last");
    print "---> load ".$argv[1]."\n";
} else {
    $fp = fopen("./.sphinx.php.last", "w");
    if(!$fp){
        throw new Zend_Exception("cannot open cache file:.sphinx.php.last");
    }
    fwrite($fp, $argv[1]);
    fclose($fp);
}
$metafile = $argv[1];
$m = new MetaProcessor();
$m->load($metafile);
$xmlArray = $m->xmlArray();
$pages = $xmlArray["meta_explanation"];
if(!isset($pages["sphinx_page"])){
    print "No sphinx page node in the meta.xml\n";
    exit;
}


$sqldir = dirname($metafile);
$sphinx_dir = dirname($sqldir)."/doc/sphinx";

if(!is_dir($sphinx_dir)){
    print "No sphinx project found.\n";
    print "--> please execute following:\n";
    print "1. mkdir $sphinxdir\n";
    print "2. cd $sphinxdir\n";
    print "3. sphinx-quickstart\n";
    print " and then retry this comand\n";
    exit;
}

$modname = $xmlArray["module"]["name"];
foreach($pages["sphinx_page"] as $i => $document){
    $filename = $modname."_".$i.".rst";
    $filepath = $sphinx_dir."/".$filename;
    print "output ===> $filename\n";
    $fp = fopen($filepath, "w");
    if(!$fp){
        print "cannot open file: $filepath\n";
        exit;
    }
    fwrite($fp, $document);
    fclose($fp);
}

system("cd $sphinx_dir; make html");

echo $res;

print "\ncheck browser $sphinx_dir/_build/html/index.html\n";
print "\tfirefox $sphinx_dir/_build/html/index.html\n";
//if(is_array($pages["sphinx_page"])){
//print_r($pages["sphinx_page"]);
//}
