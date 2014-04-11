<?php
/**
 * Meta
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
 * meta.csv を読み込んで sqlファイルと recipe ファイルを作成する
 */

class Meta 
{
    private $tables; // テーブル定義の配列
    private $mod; // module 情報の配列
    private $db; // DB 情報の配列
    private $recipe; // レシピ配列

    private $sqldir;
    private $table;
    private $note;

    public function __construct()
    {
        $this->sqldir = null;
        $this->table = null;
        $this->mod = null;
        $this->db = null;
        $this->note = null;
    }
    public function load($file)
    {
        if(!file_exists($file)){
            print("not found: $file\n");
            exit;
        }

        $this->sqldir = realpath(dirname($file));

        $lines = file($file);

        $tables = array();
        $recipe = array();
        $in_db = false;
        $in_table = false;
        $in_recipe = false;
        foreach($lines as $line){
            // コメント行、空行はスキップ
            if(preg_match("/^#.*/", $line)) continue;
            if(preg_match("/^\n/", $line)) continue;

            // moduleセクション
            if(preg_match("/^\[\[module\]\]$/", $line, $m)){
                $in_mod = true;
                continue;
            }
            if(preg_match("/^\[\[\/module\]\]$/", $line, $m)){
                $in_mod = false;
                continue;
            }
            if($in_mod){
                list($key, $val) = explode(":", $line);
                $mod[trim($key)] = trim($val);
                continue;
            }

            // DBセクション
            if(preg_match("/^\[\[db\]\]$/", $line, $m)){
                $in_db = true;
                continue;
            }
            if(preg_match("/^\[\[\/db\]\]$/", $line, $m)){
                $in_db = false;
                continue;
            }
            if($in_db){
                list($key, $val) = explode(":", $line);
                $db[trim($key)] = trim($val);
                continue;
            }

            // テーブル定義
            if(preg_match("/^\[\[(table.+)\]\]$/", $line, $m)){
                $_tblinfo = explode(",", $m[1]);
                foreach($_tblinfo as $_info){
                    list($key, $val) = explode(":", $_info);
                    $val = trim($val);
                    $key = trim($key);
                    $this->$key = $val;
                }
                $tables[$this->table]["note"] = $this->note;
                $in_table = true;
                continue;
            }
            if(preg_match("/^\[\[\/table\]\]$/", $line, $m)){
                $in_table = false;
                $this->table   = null;
                $this->note    = null;
                continue;
            }

            if($in_table) {
                if(!$this->table){
                    print "cannot found tablename\n";
                    exit;
                }
                //list($col,$type,$null,$key,$def,$ext,$label,$opt) = explode(",", $line);
                $t = explode(",", $line);
                $col = $t[0];
                $type = $t[1];
                $null = $t[2];
                $key = $t[3];
                $def = $t[4];
                $ext   = $t[5];
                $label = $t[6];
                $opt = null;
                if(isset($t[7])){ // 8個目の要素があったら $opt 配列を作る
                    $_items = explode("|", trim($t[7]));
                    $opt_array = array();
                    foreach($_items as $_i){
                        $tmp = explode("=", $_i);
                        $tmp = array_map("trim", $tmp);
                        $opt_array[$tmp[0]] = $tmp[1];
                    }
                    // よろしくないけど、再度 $opt に入れ直す
                    $opt = $opt_array;
                }
                $tables[$this->table]["columns"][trim($col)] = array(
                    "COLUMN_NAME" => trim($col),
                    "DATA_TYPE" => trim($type),
                    "NULLABLE" => strtolower(trim($null)) == "not null" ? 0 : 1,
                    "DEFAULT" => trim($def),
                    "PRIMARY" => trim($key),
                    "EXTRA" => trim($ext),
                    "LABEL" => trim($label),
                    "OPTION" => $opt,
                );
                continue;
            }

            // レシピ
            if(preg_match("/^\[\[recipe\]\]$/", $line, $m)){
                $in_recipe = true;
                continue;
            }
            if(preg_match("/^\[\[\/recipe\]\]$/", $line, $m)){
                $in_recipe = false;
                continue;
            }
            if($in_recipe){
                $line = trim($line);
                $line = preg_replace("/[ ]+/", "\t", $line);
                list($tbl, $target) = explode("\t", $line);
                $recipe[] = $tbl." ".$mod["name"]." ".$target;
            }
    
        }
        $this->tables = $tables;
        $this->recipe = $recipe;
        $this->db = $db;
        $this->mod = $mod;
    }

    public function generateSqlSkelton()
    {
        $sqlfile = $this->sqldir."/".$this->db["type"].".sql";
        print "=====> Generate Sql Skelton: $sqlfile\n";
        $fp = fopen($sqlfile, "w");
        if(!$fp){
            print "cannot open: ".$sqlfile."\n";
            exit;
        }
        foreach($this->tables as $table_name => $item){
            $primary = null;
            fputs($fp, "DROP TABLE IF EXISTS <PREFIX>_<MODULE>_{$table_name};\n");
            fputs($fp, "CREATE TABLE <PREFIX>_<MODULE>_{$table_name} (\n");
            foreach($item["columns"] as $colitem){
                $col = $colitem["COLUMN_NAME"];
                $type = $colitem["DATA_TYPE"];
                $not_null = $colitem["NULLABLE"] ? "" : " not null";
                $ext = $colitem["EXTRA"] ? " ".$colitem["EXTRA"] : "";
                if($colitem["PRIMARY"] && $primary == ""){
                    $primary = $col;
                }
                fputs($fp, "    $col $type$not_null$ext,\n");
            }
            fputs($fp, "    primary key($primary)\n");
            $engine = $this->db["engine"];
            fputs($fp, ") ENGINE={$engine} DEFAULT CHARSET=<CHARSET>;\n\n");
        }
        fclose($fp);
        print ".....done\n";
    }
    public function generateRecipefile()
    {
        $file = $this->sqldir."/recipe";
        print "=====> Generate Recipe: $file\n";
        $fp = fopen($file, "w");
        if(!$fp){
            print "cannot open: ".$file."\n";
            exit;
        }
        foreach($this->recipe as $item){
            fputs($fp, $item."\n");
        }
        fclose($fp);
        print ".....done\n";
    }

    public function tables()
    {
        return $this->tables;
    }
    public function recipe()
    {
        return $this->recipe;
    }
    public function db()
    {
        return $this->db;
    }
    public function showNextStep()
    {
        print "\n";
        print "Next step:\n";
        print "1. /bin/sh alter.sh ".$this->mod["name"]." ".$this->sqldir."/".$this->db["type"].".sql\n";
        print "2. /bin/sh cook.sh ".$this->sqldir."/recipe\n";
    }
}

