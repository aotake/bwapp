<?php
/**
 * CodeTool
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

$v = phpversion();
if($v >= "5.1.0"){
    date_default_timezone_set("Asia/Tokyo");
}

/*
 * meta.xml を読み込んで sqlファイルと recipe ファイルを作成する
 *
 * Meta.php の後継
 */

class MetaProcessor
{
    // EXCEL出力用
    const HEAD_START_LINE = 3;
    const XLS_HEAD_BGCOLOR = "000066";
    const XLS_LINE_HEIGHT = 14;

    private $tables; // テーブル定義の配列
    private $mod; // module 情報の配列
    private $db; // DB 情報の配列
    private $recipe; // レシピ配列
    private $config_def; // config.ini の中身（文字列）

    private $sqldir;
    private $table;
    private $note;
    private $memo;

    private $excelObj;
    private $sheet_number;
    private $sheet_list;
    private $xls_table_count;

    public function __construct()
    {
        $this->sqldir = null;
        $this->tables = array();
        $this->mod = null;
        $this->db = null;
        $this->note = null;
        $this->memo = null;
        $this->excelObj = null;
        $this->sheet_number = 0;
        $this->sheet_list = array();
        $this->xls_table_count = 0;

        $this_dir = dirname(__FILE__);
        $include_paths[] = realpath($this_dir);
        $include_paths[] = realpath($this_dir ."/../../libs");
        $include_paths[] = realpath($this_dir ."/../../libs/smarty");
        $include_paths[] = realpath($this_dir ."/../extlibs");
        ini_set("include_path", implode(":", $include_paths));
        require_once 'Zend/Loader/Autoloader.php';
        Zend_Loader_Autoloader::getInstance()
            ->setFallbackAutoloader(true)
            ->pushAutoloader(NULL, 'Smarty_' );
        $tool = new CodeTool();
        $tool->setupEnv();

        $target_default = realpath($this_dir."/../config/config.ini");
        $target_custom = realpath($this_dir."/../config/custom/config.ini");
        if(file_exists($target_custom)){
            $target_file = $target_custom;
        } else {
            $target_file = $target_default;
        }

        $target_section = $tool->getApplicationEnv();
        $tool->loadConfig($target_file, $target_section);

    }
    public function load($file)
    {
        if(!file_exists($file)){
            print("not found: $file\n");
            // ディレクトリチェック
            $moddir = dirname(dirname($file));
            if(!file_exists($moddir)){
                if(!mkdir($moddir, 0755)){
                    print "Error: cannot create directory: $moddir\n";
                    exit(1);
                } else {
                    print "----> create drecotry: ".$moddir."\n";
                }
            }
            $skel = file_get_contents("./skel/meta.xml");
            if(!$skel){
                print "----> Error: cannot fetch skel/meta.xml";
                exit(1);
            }
            if(!file_exists($moddir."/sql")){
                if(!mkdir($moddir."/sql", 0755)){
                    print "Error: cannot create directory: $moddir/sql\n";
                    exit(1);
                } else {
                    print "----> create drecotry: ".$moddir."/sql\n";
                }
            }
            $fp = fopen($file, "w");
            if(!$fp){
                print "----> Error: cannot create skelton file to $file";
                exit(1);
            }
            fputs($fp, $skel);
            fclose($fp);
            print("----> create meta.xml skelton as $file\n");
            print "Please edit meta.xml and run this script again\n";
            exit;
        }

        $this->sqldir = realpath(dirname($file));
        $xml = new Ao_Util_Xml();
        $xml_data = $xml->load($file);
        $this->xml_array = $xml_data->toArray();

        // table 要素が一つの時配列の要素一つとして登録し直す
        if(isset($this->xml_array["table"]["def"])){
            $tabledef = $this->xml_array["table"];
            $this->xml_array["table"] = array($tabledef);
        }
        if(isset($this->xml_array["meta_explanation"]["sphinx_page"])
            && !is_array($this->xml_array["meta_explanation"]["sphinx_page"])){

            $str = trim($this->xml_array["meta_explanation"]["sphinx_page"]);
            if($str != ""){
                $this->xml_array["meta_explanation"]["sphinx_page"] = array($str);
            }
        }
        return $this;
    }
    public function xmlArray()
    {
        return $this->xml_array;
    }

    public function parseMod()
    {
        if(!array_key_exists("module", $this->xml_array)){
            print "meta.xml has no module node.\n";
            exit;
        }
        $this->mod = $this->xml_array["module"];
    }
    public function parseDb()
    {
/*
        if(!array_key_exists("db", $this->xml_array)){
            print "meta.xml has no db node.\n";
            exit;
        }
        $this->db = $this->xml_array["db"];
*/
        $reg = Zend_Registry::getInstance();
        $config = $reg["config"];
        $array["type"] = $config->db->type;
        $array["charset"] = $config->db->charset;
        // db engine
        if($config->db->type == "mysql" && isset($config->db->engine)){
            $array["engine"] = $config->db->engine;
        }
        else if($config->db->type == "mysql"){
            $array["engine"] = "InnoDB";
        } else {
            $array["engine"] = "";
        }
        // version
        if(isset($config->db->version)){
            $array["version"] = $config->db->version;
        } else {
            $array["version"] = "";
        }
        // prefix
        if(isset($config->db->prefix)){
            $array["prefix"] = $config->db->prefix;
        } else {
            $array["prefix"] = "";
        }
        $this->db = $array;
    }

    public function parseTableCsv()
    {
        $tables = array();
            foreach($this->xml_array["table"] as $item){
                $tablename = $item["name"];
                $tablenote = isset($item["note"])? $item["note"] : null;
                $tablememo = isset($item["memo"])? $item["memo"] : null;
                $tables[$tablename]["note"] = $tablenote;
                $tables[$tablename]["memo"] = $tablememo;

                // ユニークキー
                if(isset($item["unique"]) && !is_array($item["unique"])){
                    $tables[$tablename]["unique"] = array($item["unique"]);
                }
                else if(isset($item["unique"]) && is_array($item["unique"])){
                    $tables[$tablename]["unique"] = $item["unique"];
                }
                else{
                    $tables[$tablename]["unique"] = null;
                }
                // インデックス
                if(isset($item["index"]) && !is_array($item["index"])){
                    $tables[$tablename]["index"] = array($item["index"]);
                }
                else if(isset($item["index"]) && is_array($item["index"])){
                    $tables[$tablename]["index"] = $item["index"];
                }
                else{
                    $tables[$tablename]["index"] = null;
                }

                // 初期値
                if(isset($item["init_data"])){
                    $tables[$tablename]["init_data"]["cols"] = $item["init_data"]["cols"];
                    $init_data = $item["init_data"]["data"];
                    if(!is_array($init_data)){
                        $init_data = array($init_data);
                    }
                    $tables[$tablename]["init_data"]["data"] = $init_data;
                } else {
                    $tables[$tablename]["init_data"] = null;
                }

                foreach(explode("\n", trim($item["def"])) as $line){
                    if(preg_match("/^#/", $line)) continue;
                    if(preg_match("/^[\r\n]/", $line)) continue;

                    //list($col,$type,$null,$key,$def,$ext,$label) = explode(",", $line);
                    $t = explode(",", $line);
                    $col  = $t[0];
                    $type = $t[1];
                    $null = $t[2];
                    $key  = $t[3];
                    $def  = $t[4];
                    $ext  = $t[5];
                    $label= $t[6];
                    $opt  = null;
                    $form = null;
                    $attr = null;
                    if(isset($t[7]) && !preg_match("/^\s+$/", $t[7])){ // 8個目の要素があったら $opt 配列を作る
                        if(preg_match("/.+\|.+/", $t[7])){
                            $_items = explode("|", trim($t[7]));
                            $opt_array = array();
                            foreach($_items as $_i){
                                $tmp = explode("=", $_i);
                                $tmp = array_map("trim", $tmp);
                                $opt_array[$tmp[0]] = $tmp[1];
                            }
                        } else if (preg_match("/ref_db:(.+):(.+):(.+):(.+)$/", $t[7], $match)){
                            $opt_array = array(
                                "ref_db" => true,
                                "module" => trim($match[1]),
                                "model" => Ao_Util_Str::toPascal(trim($match[2])),
                                "key" => trim($match[3]),
                                "val" => trim($match[4]),
                            );
                        } else if (preg_match("/ref_conf:(.+)$/", $t[7], $match)){
                            $opt_array = array(
                                "ref_conf" => true,
                                "section" => trim($match[1]),
                            );
                        }
                        // よろしくないけど、再度 $opt に入れ直す
                        $opt = $opt_array;
                    }
                    if(isset($t[8])){ // 9個目の要素があったら $form 配列を作る
                        $form = strtolower(trim($t[8]));
                        if(in_array($form, array("radio", "select", "checkbox"))){
                        }
                    }
                    $sep = null; // radio 用特殊変数
                    if(isset($t[9])){ // 10個目の要素があったらフォームの属性値処理する
                        $attr_def = strtolower(trim($t[9]));
                        //if(preg_match("/.+\|.+/", $attr_def)){
                            $_items = explode("|", trim($attr_def));
                            $attr_array = array();
                            foreach($_items as $_i){
                                $tmp = explode("=", $_i);
                                $tmp = array_map("trim", $tmp);
                                $attr_array[$tmp[0]] = $tmp[1];
                            }
                            // radio 用特殊要素 sep=br|nbsp があったら別途保存
                            if(isset($attr_array["sep"])){
                                $sep = $attr_array["sep"];
                                unset($attr_array["sep"]);
                            }
                        //}
                        $attr = $attr_array;
                    }
                    $tables[$tablename]["columns"][trim($col)] = array(
                        "COLUMN_NAME" => trim($col),
                        "DATA_TYPE" => trim($type),
                        "NULLABLE" => strtolower(trim($null)) == "not null" ? 0 : 1,
                        "DEFAULT" => trim($def),
                        "PRIMARY" => trim($key),
                        "EXTRA" => trim($ext),
                        "LABEL" => trim($label),
                        "OPTION" => $opt,
                        "FORM" => trim($form),
                        "FORM_ATTRIBUTE" => $attr,
                        "SEPARATOR" => trim($sep),
                    );
                }
            }
        $this->tables = $tables;
        return $this;
    }
    public function parseRecipeDef()
    {
        $recipe = array();
        if(!empty($this->xml_array["recipe"])){
            foreach(explode("\n", trim($this->xml_array["recipe"])) as $line){
                $line = trim($line);
                $line = preg_replace("/[ ]+/", "\t", $line);
                list($tbl, $target) = explode("\t", $line);
                $recipe[] = $tbl." ".$this->mod["name"]." ".$target;
            }
        }
        $this->recipe = $recipe;
        return $this;
    }
    public function parseConfigDef()
    {
        if(isset($this->xml_array["default_config"])){
            $this->config_def = trim($this->xml_array["default_config"]);
        }
        return $this;
    }

    public function generateSqlSkelton()
    {
        // ファイルチェック
        $sqlfile = $this->sqldir."/".$this->db["type"].".sql";
        print "=====> Generate Sql Skelton: $sqlfile\n";
        if(file_exists($sqlfile)){
            rename($sqlfile, $sqlfile.".bk");
            print "-----> backup old sqlfile: $sqlfile.bk\n";
        }

        // 生成処理
        if($this->db["type"] == "pgsql"){
            return $this->generateSqlSkeltonForPgsql($sqlfile);
        } else {
            return $this->generateSqlSkeltonForMysql($sqlfile);
        }
    }
    public function generateSqlSkeltonForMysql($sqlfile)
    {
        $fp = fopen($sqlfile, "w");
        if(!$fp){
            print "cannot open: ".$sqlfile."\n";
            exit;
        }
        foreach($this->tables as $table_name => $item){
            $primary = null;
            if($this->mod["name"] == "default"){
                fputs($fp, "DROP TABLE IF EXISTS <PREFIX>_{$table_name};\n");
                fputs($fp, "CREATE TABLE <PREFIX>_{$table_name} (\n");
            } else {
                fputs($fp, "DROP TABLE IF EXISTS <PREFIX>_<MODULE>_{$table_name};\n");
                fputs($fp, "CREATE TABLE <PREFIX>_<MODULE>_{$table_name} (\n");
            }
            foreach($item["columns"] as $colitem){
                $col = $colitem["COLUMN_NAME"];
                $type = $colitem["DATA_TYPE"];
                $default = trim($colitem["DEFAULT"]);
                if(is_numeric($default) && preg_match("/(smallint|tinyint|int)/", $type)){
                    $default = " default ".$default;
                } else if( $default != "" ){
                    $default = " default '".$default."'";
                } else {
                    $default = null;
                }
                $not_null = $colitem["NULLABLE"] ? "" : " not null";
                $ext = $colitem["EXTRA"] ? " ".$colitem["EXTRA"] : "";
                if($colitem["PRIMARY"] && $primary == ""){
                    $primary = $col;
                }
                fputs($fp, "    $col $type$not_null$default$ext,\n");
            }
            fputs($fp, "    primary key($primary)\n");
            $engine = $this->db["engine"];
            fputs($fp, ") ENGINE={$engine} DEFAULT CHARSET=<CHARSET>;\n\n");

            // テーブル名をモジュール名で切り分け
            if($this->mod["name"] == "default"){
                    $t = "<PREFIX>_{$table_name}";
            } else {
                    $t = "<PREFIX>_<MODULE>_{$table_name}";
            }

            // インデックス作成
            //      see: http://d.hatena.ne.jp/tilfin/20080209/1202544867
            //      see: http://phpjavascriptroom.com/?t=mysql&p=index#a_create_index
            if($item["unique"]){
                foreach($item["unique"] as $idx){
                    fputs($fp,"ALTER TABLE {$t} ADD UNIQUE($idx);\n");
                }
            }
            if($item["index"]){
                foreach($item["index"] as $idx){
                    fputs($fp,"ALTER TABLE {$t} ADD INDEX($idx);\n");
                }
            }

            // 初期データがあれば INSERT 文をつくる
            if($item["init_data"]){

                foreach($item["init_data"]["data"] as $d){
                    fputs($fp, "INSERT INTO {$t} (".$item["init_data"]["cols"].") VALUES (".$d.");\n");
                }
                fputs($fp, "\n");
            }
        }
        fclose($fp);
        print ".....done\n";
    }
    public function generateSqlSkeltonForPgsql($sqlfile)
    {
        $fp = fopen($sqlfile, "w");
        if(!$fp){
            print "cannot open: ".$sqlfile."\n";
            exit;
        }
        foreach($this->tables as $table_name => $item){
            $primary = null;
            if($this->mod["name"] == "default"){
                fputs($fp, "DROP TABLE IF EXISTS <PREFIX>_{$table_name};\n");
                fputs($fp, "CREATE TABLE <PREFIX>_{$table_name} (\n");
            } else {
                fputs($fp, "DROP TABLE IF EXISTS <PREFIX>_<MODULE>_{$table_name};\n");
                fputs($fp, "CREATE TABLE <PREFIX>_<MODULE>_{$table_name} (\n");
            }
            foreach($item["columns"] as $colitem){
                $col = $colitem["COLUMN_NAME"];
                $type = $colitem["DATA_TYPE"];
                if(trim($colitem["DEFAULT"]) &&preg_match("/(smallint|tinyint|int)/", $type)){
                    $default = " default ".$colitem["DEFAULT"];
                } else if( trim($colitem["DEFAULT"]) ){
                    $default = " default '".$colitem["DEFAULT"]."'";
                } else {
                    $default = null;
                }
                $not_null = $colitem["NULLABLE"] ? "" : " not null";
                $ext = $colitem["EXTRA"] ? " ".$colitem["EXTRA"] : "";
                if($colitem["PRIMARY"] && $primary == ""){
                    $primary = $col;
                }
                // PostgreSQL 用の型変換
                // auto_increment のカラムは serial型にする
                if($colitem["EXTRA"] == "auto_increment"){
                    fputs($fp, "    $col serial,\n");
                }
                // tinyint は smallint 型にする
                else if(preg_match("/^tinyint.*/",$colitem["DATA_TYPE"])){
                    fputs($fp, "    $col smallint$not_null$ext,\n");
                }
                // float は real 型とする
                else if(preg_match("/^float.*/",$colitem["DATA_TYPE"])){
                    fputs($fp, "    $col real$not_null$ext,\n");
                }
                // double は double precision型とする
                else if(preg_match("/^double.*/",$colitem["DATA_TYPE"])){
                    fputs($fp, "    $col double precision $not_null$ext,\n");
                }
                else{
                    fputs($fp, "    $col $type$not_null$default$ext,\n");
                }
            }
            fputs($fp, "    primary key($primary)\n");
            $engine = $this->db["engine"];
            //fputs($fp, ") ENGINE={$engine} DEFAULT CHARSET=<CHARSET>;\n\n");
            fputs($fp, ");\n\n");

            // テーブル名特定
            if($this->mod["name"] == "default"){
                $t = "<PREFIX>_{$table_name}";
            } else {
                $t = "<PREFIX>_<MODULE>_{$table_name}";
            }

            // インデックス作成
            //      see: http://lib.stwing.jp/archives/2006/12/postgresqlalter.html
            if($item["unique"]){
                foreach($item["unique"] as $idx){
                    $keyname = str_replace(",", "_", str_replace(" ","",$idx))."_key";
                    fputs($fp, "ALTER TABLE {$t} ADD CONSTRAINT $keyname UNIQUE($idx);\n");
                }
            }
            if($item["index"]){
                foreach($item["index"] as $idx){
                    $keyname = str_replace(",", "_", str_replace(" ","",$idx))."_key";
                    fputs($fp,"CREATE INDEX {$keyname} ON {$t} ($idx);\n");
                }
            }

            // 初期データがあれば INSERT 文をつくる
            if($item["init_data"]){

                foreach($item["init_data"]["data"] as $d){
                    fputs($fp, "INSERT INTO {$t} (".$item["init_data"]["cols"].") VALUES (".$d.");\n");
                }
                fputs($fp, "\n");
            }
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
    public function generateConfigIni()
    {
        $config_dir = dirname($this->sqldir)."/config";
        $custom_dir = dirname($this->sqldir)."/config/custom";
        //$file = $config_dir."/config.ini";
        $file = $custom_dir."/config.ini";
        print "=====> Generate config.ini: $file\n";
        if(!is_dir($config_dir)){
            mkdir($config_dir, 0755);
            print "-----> mkdir: $config_dir\n";
        }
        if(!is_dir($custom_dir)){
            mkdir($custom_dir, 0755);
            print "-----> mkdir: $custom_dir\n";
        }
        if($this->config_def){
            if(file_exists($file)){
                print "---------> found: $file\n";
                print "---------> backup: $file\n";
                if(file_exists($file.".bak")){
                    unlink($file.".bak");
                }
                copy($file, $file.".bak");
            }
            $fp = fopen($file, "w");
            if(!$fp){
                print "cannot open: ".$file."\n";
                exit;
            }
            fputs($fp, $this->config_def."\n");
            fclose($fp);
            print ".....done\n";
        } else {
            print "-----> not found: default config.\n";
            print ".....skip\n";
        }
    }

    public function countTable($metafiles)
    {
        $count = 0;
        foreach($metafiles as $f){
            $this->load($f);
            $count += (int)count($this->xml_array["table"]);
        }
        $this->xls_table_count = $count;
        return $count;
    }
    private function xlsCellSetTitle(&$sheet, $row, $line)
    {
        // タイトル背景色
        $sheet->getStyle($row.$line)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle($row.$line)->getFill()->getStartColor()->setRGB(self::XLS_HEAD_BGCOLOR);
        $sheet->getStyle($row.$line)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
        $sheet->getStyle($row.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($row.$line)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    }
    public function createExcelObject()
    {
        // オブジェクト生成
        if(!$this->excelObj){
            $this->excelObj = new PHPExcel();
            $this->sheet_number = 0;
        }
    }
    public function clearExcelObject()
    {
        $this->excelObj = null;
    }
    public function generateDbDefinitionExcel()
    {
        $x =& $this->excelObj;

        $sheet_number =& $this->sheet_number;
        foreach($this->tables as $table_name => $item){
            print $this->mod["name"]." module, $table_name => add to sheet$sheet_number\n";
            // シート
            if($sheet_number == 0){
                $sheet_number++;
            }
            //if($sheet_number > 0){
                $x->createSheet();
            //}
            $x->setActiveSheetIndex($sheet_number);
            $sheet = $x->getActiveSheet();

            if(isset($this->mod["type"]) && $this->mod["type"] == "system"){
                $tbl = $table_name;
            } else {
                $tbl = $this->mod["name"]."_".$table_name;
            }

            // シート名はテーブル名で設定する
            $sheet->setTitle($tbl);

            $this->sheet_list[$this->mod["name"]][] = array(
                "sheet" => $sheet_number,
                "table" => $tbl,
                "note" => $item["note"],
            );

            // カラム幅設定
            $sheet->getColumnDimension("A")->setWidth(29.83);
            $sheet->getColumnDimension("B")->setWidth(19.83);
            $sheet->getColumnDimension("C")->setWidth(10.67);
            $sheet->getColumnDimension("D")->setWidth(7.83);
            $sheet->getColumnDimension("E")->setWidth(24.83);
            $sheet->getColumnDimension("F")->setWidth(19.83);
            $sheet->getColumnDimension("G")->setWidth(31.83);
            $sheet->getColumnDimension("H")->setWidth(7.83);
            $sheet->getColumnDimension("I")->setWidth(7.83);

            // ヘッダ部分
            $hlink = new PHPExcel_Cell_Hyperlink("'sheet://目次'!A1");
            $sheet->setHyperlink("A1", $hlink);
            $sheet->setCellValue("A1", "データベーステーブル定義書");
            $sheet->mergeCells("A1:I1");

            $line = self::HEAD_START_LINE;
            $sheet->setCellValue("A".$line, "作成");
            $sheet->mergeCells("B$line:G$line");
            $sheet->setCellValue("H".$line, "頁数");
            $sheet->setCellValue("I".$line, $sheet_number."/".$this->xls_table_count);
            $line++;

            $sheet->setCellValue("A".$line, "モジュール名");
            $sheet->setCellValue("B".$line, $this->mod["name"]);
            $sheet->mergeCells("B$line:I$line");
            $line++;

            $sheet->setCellValue("A".$line, "テーブルPrefix");
            $sheet->setCellValue("B".$line, $this->db["prefix"]);
            $sheet->mergeCells("B$line:I$line");
            $line++;

            $sheet->setCellValue("A".$line, "テーブル名");
            $sheet->setCellValue("B".$line, $tbl);
            $sheet->mergeCells("B$line:I$line");
            $line++;

            $sheet->setCellValue("A".$line, "注釈");
            $sheet->setCellValue("B".$line, $item["note"]);
            $sheet->mergeCells("B$line:I$line");
            $line++;

            $sheet->setCellValue("A".$line, "RDBMS");
            if($this->db["type"] == "pgsql"){ 
                $rdbms = "PostgreSQL";
            } else if($this->db["type"] == "mysql"){ 
                $rdbms = "MySQL";
            }
            if($this->db["version"] != ""){
                $rdbms .= " (ver ".$this->db["version"].")";
            }
            $sheet->setCellValue("B".$line, $rdbms);
            $sheet->mergeCells("B$line:I$line");
            $line++;

            $sheet->setCellValue("A".$line, "文字コード");
            $sheet->setCellValue("B".$line, $this->db["charset"]);
            $sheet->mergeCells("B$line:I$line");
            $line++;

            $sheet->setCellValue("A".$line, "備考");
            $sheet->setCellValue("B".$line, str_replace("。", ". ", trim($item["memo"])));
            $sheet->mergeCells("B$line:I$line");
            // 自動改行
            $sheet->getStyle("B$line")->getAlignment()->setWrapText(true);
            // 行高さ設定（setRowHeightにパラメタ指定しないことで自動調整）
            //$sheet->getRowDimension($line)->setRowHeight();
            //$sheet->getColumnDimension("B$line")->setAutoSize();
            $col_line_count = count(explode("\n", trim($item["memo"])));
            $sheet->getRowDimension($line)->setRowHeight(self::XLS_LINE_HEIGHT * $col_line_count);
            $line++;

            // タイトル背景色
            $this->xlsCellSetTitle($sheet, "A", 1);
            for($i = self::HEAD_START_LINE; $i < $line-1; $i++){
                $this->xlsCellSetTitle($sheet, "A", $i);
            }
            $this->xlsCellSetTitle($sheet, "H", self::HEAD_START_LINE);
            $this->xlsCellSetTitle($sheet, "A", $i);

            // ヘッダ部分の罫線
            $cell_style = array(
                'borders' => array(
                    'top'     => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                    'bottom'  => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                    'left'    => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                    'right'   => array('style' => PHPExcel_Style_Border::BORDER_THIN)
                ),
            );
            for($i = self::HEAD_START_LINE; $i < $line; $i++){
                $sheet->getStyle("A$i")->applyFromArray($cell_style);
                $sheet->getStyle("B$i:I$i")->applyFromArray($cell_style);
            }

            // テーブル定義
            $line++;
            $sheet->setCellValue("A".$line, "カラム名");
            $sheet->setCellValue("B".$line, "データ型");
            $sheet->setCellValue("C".$line, "Null");
            $sheet->setCellValue("D".$line, "Key");
            $sheet->setCellValue("E".$line, "Default");
            $sheet->setCellValue("F".$line, "Extra");
            $sheet->setCellValue("G".$line, "注釈");
            $sheet->mergeCells("G$line:I$line");

            // タイトル背景色, 罫線
            foreach(array("A", "B", "C", "D", "E", "F", "G", "H", "I") as $row){
                $this->xlsCellSetTitle($sheet, $row, $line);
                $sheet->getStyle("$row$line")->applyFromArray($cell_style);
            }

            $line++;
            foreach($item["columns"] as $colitem){
                $col = $colitem["COLUMN_NAME"];
                $type = $colitem["DATA_TYPE"];
                $not_null = $colitem["NULLABLE"] ? "" : "not null";
                $ext = $colitem["EXTRA"] ? $colitem["EXTRA"] : "";
                $primary = isset($colitem["PRIMARY"]) && $colitem["PRIMARY"] != "" ? "PRIMARY" : "";
                $default = $colitem["DEFAULT"];
                $label = $colitem["LABEL"];

                // セルの値
                $sheet->setCellValue("A".$line, $col);
                $sheet->setCellValue("B".$line, $type);
                $sheet->setCellValue("C".$line, $not_null);
                $sheet->setCellValue("D".$line, $primary);
                $sheet->setCellValue("E".$line, $default);
                $sheet->setCellValue("F".$line, $ext);
                $sheet->setCellValue("G".$line, $label);
                $sheet->mergeCells("G$line:I$line");

                // 罫線
                foreach(array("A", "B", "C", "D", "E", "F", "G", "H", "I") as $row){
                    $sheet->getStyle("$row$line")->applyFromArray($cell_style);
                }

                $line++;
            }
            //(5)スタイルの設定
            //$sheet->getDefaultStyle()->getFont()->setName("MS P ゴシック");
            //$sheet->getDefaultStyle()->getFont()->setSize(11);
            //$sheet->getStyle('C3')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            //$sheet->getStyle('C3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            // インデックス情報
            $line++;
            foreach(array("index", "unique") as $idx_type){
                $sheet->setCellValue("A".$line, strtoupper($idx_type));
                $this->xlsCellSetTitle($sheet, "A", $line);
                if($item[$idx_type]){
                    $topline = $line;
                    foreach($item[$idx_type] as $idx){
                        $sheet->setCellValue("B".$line, $idx);
                        $sheet->mergeCells("B$line:I$line");
                        foreach(array("A", "B", "C", "D", "E", "F", "G", "H", "I") as $row){
                            $sheet->getStyle("$row$line")->applyFromArray($cell_style);
                        }
                        $line++;
                    }
                    $sheet->mergeCells("A$topline:A".($line-1));
                    //$sheet->getStyle("A".$topline)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                } else {
                    $sheet->setCellValue("B".$line, "設定無し");
                    $sheet->mergeCells("B$line:I$line");
                    foreach(array("A", "B", "C", "D", "E", "F", "G", "H", "I") as $row){
                        $sheet->getStyle("$row$line")->applyFromArray($cell_style);
                    }
                    $line++;
                }
            }

            $sheet_number++;
        }
    }
    public function generateToc()
    {
        $x =& $this->excelObj;
        $sheet_number =& $this->sheet_number;

        //$x->createSheet();
        $x->setActiveSheetIndex(0);
        $sheet = $x->getActiveSheet();
        $sheet->setTitle("目次");
        $line = 1;
        $sheet->setCellValue("A".$line, "データベーステーブル定義書");
        $sheet->mergeCells("A1:C1");
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension("A")->setWidth("20.65");
        $sheet->getColumnDimension("B")->setWidth("40.65");
        $sheet->getColumnDimension("C")->setWidth("45.65");

        $line = 3;
        $sheet->setCellValue("A".$line, "モジュール名");
        $sheet->setCellValue("B".$line, "テーブル名");
        $sheet->setCellValue("C".$line, "備考");

        $this->xlsCellSetTitle($sheet, "A", 1);
        foreach(array("A","B","C") as $row){
            $this->xlsCellSetTitle($sheet, $row, $line);
        }

        // 罫線設定
        $cell_style = array(
            'borders' => array(
                'top'     => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'bottom'  => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'left'    => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right'   => array('style' => PHPExcel_Style_Border::BORDER_THIN)
            ),
        );

        $line++;
        foreach($this->sheet_list as $module => $tablelist){
            $sheet->setCellValue("A".$line, $module);
            $line_modtop = $line;
            foreach($tablelist as $item){
                $sheet->setCellValue("B".$line, $item["table"]);
                $sheet->setCellValue("C".$line, $item["note"]);

                $hlink = new PHPExcel_Cell_Hyperlink("'sheet://".$item["table"]."'!A1");
                $sheet->setHyperlink("B".$line, $hlink);
                $sheet->setHyperlink("C".$line, $hlink);
                // 罫線
                foreach(array("A", "B", "C") as $row){
                    $sheet->getStyle("$row$line")->applyFromArray($cell_style);
                }
                $line++;
            }
            $sheet->mergeCells("A".$line_modtop.":A".($line-1));
            $sheet->getStyle("A".$line_modtop)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            //$sheet->getStyle("A".$line_modtop)->getAlignment()->setVirtical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }

        $this->sheet_list = array(); // clear
        $sheet_number++;
    }
    public function writeExcel($target_all = false){
        //Excel2007形式で保存
        $writer = PHPExcel_IOFactory::createWriter($this->excelObj, "Excel2007");
        // ファイル名
        if($target_all){
            $output = "DbDefinition.xlsx";
            $writer->save($output);
        } else {
            $output = "DbDefinition_".$this->mod["name"].".xlsx";
            $writer->save($output);
            $this->clearExcelObject();
        }
        print "---> save: $output\n\n";
    }

    public function mod()
    {
        return $this->mod;
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
    public function sheetList()
    {
        return $this->sheet_list;
    }
    public function showNextStep()
    {
        print "\n";
        print "Next step:\n";
        print "1. /bin/sh alter.sh ".$this->mod["name"]." ".$this->sqldir."/".$this->db["type"].".sql\n";
        print "2. /bin/sh cook.sh ".$this->sqldir."/recipe\n";
    }
}

