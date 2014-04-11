<?php
/**
 * convert 'new xxx' statement to Manager::getXxxx()
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
if(phpversion() >= "5.1.0"){ date_default_timezone_set("Asia/Tokyo"); }

/*
 * controller,Manager,Form のなかで new XXX() している部分を Manager::getXxxx() に置換する 
 */
require_once "CodeTool.php";
$tool = new CodeTool();
$tool->setupEnv();

if($argc < 2){
    //print "php ".$argv[0]." <modname> <controller_dir_path>\n";
    print "This script convert \"new Xxx()\" statements by Manager::getXxx().\n";
    print "Usage: php ".$argv[0]." <modname>\n";
    exit;
}
$modname = $argv[1];
/*
$ctr_dir = $argv[2];
if(substr($ctr_dir, -1, 1) == "/"){
    $ctr_dir = substr($ctr_dir, 0, -1);
}
*/

// ディレクトリ設定
$ctr_dir = "../modules/".$modname."/controllers";
$mng_dir = "../modules/".$modname."/libs/".Ao_Util_Str::toPascal($modname)."/Manager";
$form_dir = "../modules/".$modname."/libs/".Ao_Util_Str::toPascal($modname)."/Form";

$mng_basedir = "../modules/".$modname."/libs/base/".Ao_Util_Str::toPascal($modname)."Base/Manager";
$form_basedir = "../modules/".$modname."/libs/base/".Ao_Util_Str::toPascal($modname)."Base/Form";

// パターン
$pat_manager = "/new ([A-Z]{1}[A-Za-z0-9]+)_Manager_([A-Za-z0-9]+)\((.+)\)/";
$pat_form = "/new ([A-Z]{1}[A-Za-z0-9]+)_Form_([A-Za-z0-9]+)\((.+)\)/";
$pat_validator = "/new ([A-Z]{1}[A-Za-z0-9]+)_Form_Validator_([A-Za-z0-9]+)\((.*)\)/";
$pat_model = "/new ([A-Z]{1}[A-Za-z0-9]+)_Model_([A-Za-z0-9]+)\((.*)\)/";

// ファイルリスト作成
$files = glob($ctr_dir."/*Controller.php");
$files = array_merge($files, glob($mng_dir."/*.php"));
$files = array_merge($files, glob($form_dir."/*.php"));

$files = glob($ctr_dir."/Base/*BaseController.php");
$files = array_merge($files, glob($mng_basedir."/*.php"));
$files = array_merge($files, glob($form_basedir."/*.php"));

// Manager の __controller() 内に埋め込むコード(preg_replace 用)
$embed_statement = "\$this->modname = Ao_Util_Str::toSnake(preg_replace(\"/^([^_]+)_Manager_.+/\", \"\\\\\\\\1\", get_class(\$this)));";
// Manager の __controller() 内に埋め込むコード(str_replace用)
$embed_statement_strrep = "\$this->modname = Ao_Util_Str::toSnake(preg_replace(\"/^([^_]+)_Manager_.+/\", \"\\\\1\", get_class(\$this)));";
// 旧書式のもの：これらは上記 get_class を使ったコードで置き換える
$embed_statement_single = "\$this->modname = basename(dirname(dirname(dirname(dirname(__FILE__)))));"; // for normal libs path
$embed_statement_dup = "\$this->modname = basename(dirname(dirname(dirname(dirname(dirname(__FILE__))))));"; // for base libs path

// 各ファイルを変換
foreach($files as $file){
    print "===============================\n";
    print "----> $file\n";
    $fp = fopen($file, "r");
    if(!$fp){
        print "----> Error: cannot open: $file\n";
        exit;
    }
    $wfp = fopen($file.".tmp", "w");
    if(!$wfp){
        print "----> Error: cannot open: $file.tmp\n";
        fclose($fp);
        exit;
    }
    while(($line = fgets($fp, 2048)) !== false){
        if(preg_match($pat_manager, $line, $m)){
            $module = Ao_Util_Str::toSnake($m[1]);
            $name   = Ao_Util_Str::toSnake($m[2]);
            $arg    = Ao_Util_Str::toSnake($m[3]);
            print "prev: ".$line;
            $line = preg_replace($pat_manager, "Manager::getManager($arg, \"$name\")", $line);
            print "post: ".$line;
        }
        else if(preg_match($pat_form, $line, $m)){
            $module = Ao_Util_Str::toSnake($m[1]);
            $name   = Ao_Util_Str::toSnake($m[2]);
            $arg    = Ao_Util_Str::toSnake($m[3]);
            print "prev: ".$line;
            $line = preg_replace($pat_form, "Manager::getForm($arg, \"$name\")", $line);
            print "post: ".$line;
        }
        else if(preg_match($pat_validator, $line, $m)){
            $module = Ao_Util_Str::toSnake($m[1]);
            $name   = Ao_Util_Str::toSnake($m[2]);
            $arg    = Ao_Util_Str::toSnake($m[3]);
            // 古いソースだと null のことがあるので、Form で使っているということを前提として
            // $this->_controller を埋める
            if($arg == ""){
                $arg = "\$this->_controller";
            }
            print "prev: ".$line;
            $line = preg_replace($pat_validator, "Manager::getValidator($arg, \"$name\")", $line);
            print "post: ".$line;
        }
        else if(preg_match($pat_model, $line, $m)){
            $module = Ao_Util_Str::toSnake($m[1]);
            $name   = Ao_Util_Str::toSnake($m[2]);
            $arg    = Ao_Util_Str::toSnake($m[3]);
            print "prev: ".$line;
            if($module == $modname){
                if(preg_match("/Manager/", $file)){
                    $line = preg_replace($pat_model, "Manager::getModel(\$this->modname, \"$name\")", $line);
                }
                if(preg_match("/Form/", $file)){
                    $line = preg_replace($pat_model, "Manager::getModel(\$this->_controller, \"$name\")", $line);
                }
                else {
                    $line = preg_replace($pat_model, "Manager::getModel(\$this, \"$name\")", $line);
                }
            } else {
                $line = preg_replace($pat_model, "Manager::getModel(\"$module\", \"$name\")", $line);
            }
            print "post: ".$line;
        }
        fwrite($wfp,$line);
    }
    fclose($wfp);
    fclose($fp);
    unlink($file);
    rename($file.".tmp", $file);
    print "----> done.\n\n";
}

// Manager は modname 検出コードを埋め込む(あったらスキップ)
foreach($files as $file){
    print "construct check: $file\n";
    if(!preg_match("/Manager/", $file)){
        continue;
    }
    $fp = fopen($file, "r");
    if(!$fp){
        print "----> Error: cannot open: $file\n";
        exit;
    }
    $wfp = fopen($file.".tmp", "w");
    if(!$wfp){
        print "----> Error: cannot open: $file.tmp\n";
        fclose($fp);
        exit;
    }
    $modname_flag = false; // $modname 変数が定義されているか
    while(($line = fgets($fp, 2048)) !== false){
        // コンストラクタブロックのみ抽出し変換
        if(preg_match("/__construct/", $line)){
            $construct = $line;
            while(($line = fgets($fp, 2048)) !== false && !preg_match("/\}/", $line)){
                $construct.= $line;
            }
            $construct .= $line;

            // コンストラクタに modname をセットするコードがなければ埋め込む
            if(!preg_match("/this->modname[ \t]+=/", $construct)){
                $construct = preg_replace("/\{/", "{\n        $embed_statement", $construct);
            }
            // modname をセットしているけど dirname を使っている場合はまるごと書き換え
            else {
                $construct = str_replace($embed_statement_single, $embed_statement_strrep, $construct);
                $construct = str_replace($embed_statement_dup, $embed_statement_strrep, $construct);
            }

            // コンストラクタに内部変数 modname が無ければ protected で追加
            //if(!preg_match("/protected\s+\$modname;/", $construct)){
            if($modname_flag == false){
                $construct = preg_replace("/public/", "protected \$modname;\n\n    public", $construct);
            }
            //print $construct;
            fwrite($wfp,$construct);
        }
        // それ以外はそのまま出力
        else {
            //print $line;
            // protected $modname; があるかをチェック
            if(preg_match("/protected[ \t\$]+modname/", $line)){
                $modname_flag = true;
            }
            fwrite($wfp,$line);
        }
    }
    fclose($wfp);
    fclose($fp);
    unlink($file);
    rename($file.".tmp", $file);
    print "done.\n";
}
