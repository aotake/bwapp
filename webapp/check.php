<?php
/**
 * platform check script
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
 * @package       Ao.webapp
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$v = phpversion();
if($v >= "5.1.0"){
    date_default_timezone_set("Asia/Tokyo");
}
class SysCheck
{
    var $dir;
    var $status;
    var $registry;
    var $config;
    var $db;
    var $dbuse_flag;

    public function __construct()
    {
        $this->platform_dir = dirname(dirname(__FILE__));
        $this->status = array(
            "dirPermCheck" => false,
            "iniFileCheck" => false,
            "dbConnectCheck" => false,
            "tableCheck" => false,
        );
        $this->db = null;

        ini_set("include_path", $this->platform_dir."/libs:".$this->platform_dir."/webapp/libs");
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->setFallbackAutoloader(true);
        $this->registry = Zend_Registry::getInstance();
    }
    public function setupLogger()
    {
        if(!isset($this->registry["logger"])){
            $log_path = dirname(__FILE__)."/check.log";
            $logger = new Zend_Log();
            $writer = new Zend_Log_Writer_Stream($log_path);
            $logger->addWriter($writer);
            $this->registry["logger"] = $logger;
        }
    }
    public function dirPermCheck($dirs = array())
    {
        $flag = true;
        print "\n[directory permission check]\n";
        $dir = $this->platform_dir;
        foreach($dirs as $d){
            $path = $dir.$d;
            $perm = "0000";
            if(file_exists($path)){
                $perm = substr(sprintf("%o", fileperms($path)),-4);
            }
            if($perm == "0777"){
                echo "$d => writable : ok\n";
            } else {
                echo "$d => writable : ng [permission = $perm]\n";
                $_dir   = $dir;
                $_depth = explode("/", $d);
                foreach($_depth as $_d){
                    if($_d == "") continue;
                    $tmp_path = $_dir."/".$_d;

                    if(file_exists($tmp_path)){
                        echo "----> read ok : $tmp_path\n";
                    }
                    else{
                        echo "----> read ng : $tmp_path\n";
                        if(!mkdir($tmp_path)){
                            echo "--------> mkdir: false.\n";
                        }
                    }
                    $_dir .= "/".$_d;
                }

                if(!chmod($path, 0777)){
                    echo "----> chmod 777: false.\n";
                } else {
                    $flag = false;
                    echo "----> chmod 777: success.\n";
                }
            }
        }
        $this->status["dirPermCheck"] = $flag;
    }
    public function iniFileCheck($files = array())
    {
        $flag = true;
        print "\n[ini file check]\n";
        foreach($files as $file){
            $path = $this->platform_dir.$file;
            $cust_path = $this->platform_dir.dirname($file)."/custom/".basename($file);
            $info = pathinfo($file);
            if(array_key_exists("filename", $info)){ // after php ver 5.2.x
                $sample = $info["dirname"]
                    ."/".$info["filename"]
                    .".sample."
                    .$info["extension"];
            } else {                                 // before php ver 5.2
                $sample = $info["dirname"]
                    ."/".basename($info["basename"],".ini")
                    .".sample."
                    .$info["extension"];
            }
            $sample_path = $this->platform_dir.$sample;

            if(file_exists($cust_path)){
                echo "$file => custom config file found\n";
                // サンプルがあったら編集済みかをチェックする
                if(file_exists($sample_path)){
                    $diff_cmd = "diff $sample_path $cust_path";
                    $res = shell_exec($diff_cmd);
                    if($res == ""){
                        echo "----> it seems not to be edit: $file\n"
                            ."\tPlease edit with following command:\n"
                            ."\tcp $sample_path $cust_path\n"
                            ."\tvi $cust_path\n";
                        $flag = false;
                    }
                }
            } else if(file_exists($path)){
                echo "$file => found\n";

                // サンプルがあったら編集済みかをチェックする
                if(file_exists($sample_path)){
                    $diff_cmd = "diff $sample_path $path";
                    $res = shell_exec($diff_cmd);
                    if($res == ""){
                        echo "----> it seems not to be edit: $file\n"
                            ."\tPlease edit with following command:\n"
                            //."\tvi $path\n";
                            ."\tcp $sample_path $cust_path\n"
                            ."\tvi $cust_path\n";
                        $flag = false;
                    }
                }
            } else {
                echo "$file => NOT found\n";

                // サンプルがあったらコピーするよう促す
                if(file_exists($sample_path)){
                    echo "----> found sample: $sample\n";
                    echo "----> do following command:\n"
                            ."\tcp $sample_path $path\n"
                            ."\tvi $path\n";
                    $flag = false;
                }
            }
        }
        $this->status["iniFileCheck"] = $flag;
    }
    public function loadConfigIni()
    {
        print "\n[load webapp/config/config.ini]\n";
        $this->status["loadConfigIni"] = false;

        $this->status["loadConfigIni"] = false;
        if($this->status["iniFileCheck"] == false){
            echo "check ini file existence first.\n";
            return false;
        }
        $config_type = "custom";
        $ini_file = $this->platform_dir."/webapp/config/custom/config.ini";
        if(!file_exists($ini_file)){
            $config_type = "default";
            $ini_file = $this->platform_dir."/webapp/config/config.ini";
            if(!file_exists($ini_file)){
                echo "no config.ini file.\n";
                return false;
            }
        }
        echo "----> config type: $config_type\n";
        echo "----> config path: $ini_file\n";
        $config_section = $this->_getApplicationEnv();
        echo "----> APPLICATION_ENV = $config_section\n";
        if(!$config_section){
            echo "----> NOT found config section from .htaccess\n";
            return false;
        }
        $this->config = new Zend_Config_Ini($ini_file, $config_section);
        $this->registry["config"] = $this->config;
        $this->status["loadConfigIni"] = true;
        echo "----> loaded.\n";
        $this->status["loadConfigIni"] = true;
        return true;
    }
    public function checkDbUse()
    {
        $this->dbuse_flag = false;
        print "\n[checkDbUse]\n";
        if($this->status["loadConfigIni"] == false){
            echo "----> coundn't load config.ini file.\n";
            echo "----> maybe not found htaccess\n";
            return false;
        }
        $sysconf = $this->config->system;
        echo "database use: ";
        if(!isset($sysconf->use_db) || $sysconf->use_db == true){
            echo "yes\n";
            $this->dbuse_flag = true;
            return true;
        } else {
            echo "no\n";
            echo "---> skip: database check.\n\n";
            return false;
        }
    }
    public function dbConnectCheck()
    {
        print "\n[db Connect check]\n";

        if($this->status["iniFileCheck"] == false){
            echo "check ini file existence first.\n";
            return ;
        } else {
            //$ini_file = $this->platform_dir."/webapp/config/config.ini";
            //$config_section = $this->_getApplicationEnv();
            //if(!$config_section){
            //    $this->status["dbConnectCheck"] = false;
            //    return;
            //}
            //$this->config = new Zend_Config_Ini($ini_file, $config_section);
            //$this->registry["config"] = $this->config;
            $dbconf = $this->config->db->toArray();
            $dsn = $dbconf["dsn"];
            $opt_arr = array(
                "host" => $dsn["host"],
                "username" => $dsn["username"],
                "password" => $dsn["password"],
                "dbname" => $dsn["dbname"],
                "charset" => $dbconf["charset"],
            );
            if(@$dsn["port"]){
                $opt_arr["port"] = $dsn["port"];
            }
            if(array_key_exists("unix_socket", $dsn)){
                $opt_arr["unix_socket"] = $dsn["unix_socket"];
            }
            try {
                $zdb_adapter = Zend_Db::factory($dsn["driver"], $opt_arr);
                $con = $zdb_adapter->getConnection();
                if(is_null($con)){
                    echo "db connection : ng\n";
                    echo "----> getConnection return NULL\n";
                    echo "\n";
                    echo "Create database unless you did it.\n";
                    if($dbconf["type"] == "mysql"){
                        echo "----> create database ".$dsn["dbname"]
                                ." default character set ".$dbconf["charset"].";\n";
                        echo "----> grant all privileges on ".$dsn["dbname"].".* to ".$dsn["username"]."@".$dsn["host"]." identified by \"".$dsn["password"]."\";";
                    } else if($dbconf["type"] == "pgsql"){
                        if(@$dbconf["bin_path"]){
                            $bin_path = $dbconf["bin_path"];
                        } else {
                            $bin_path = "/usr/local/pgsql/bin";
                        }
                        echo "----> $bin_path/createuser --createdb --no-adduser --pwprompt ".$dsn["username"]."\n";
                        echo "--------> password is ".$dsn["password"]."\n";
                        echo "----> $bin_path/createdb -U ".$dsn["username"]." -E ".$dbconf["charset"]." ".$dsn["dbname"]."\n";
                    }
                    $this->status["dbConnectCheck"] = false;
                    return false;
                }
                Zend_Db_Table_Abstract::setDefaultAdapter($zdb_adapter);
            }
            catch(Exception $e){
                echo "db connection : ng\n";
                echo "----> ".$e->getMessage()."\n";
                echo "\n";
                echo "Create database unless you did it.\n";
                if($dbconf["type"] == "mysql"){
                    echo "----> create database ".$dsn["dbname"]
                            ." default character set ".$dbconf["charset"].";\n";
                    echo "----> grant all privileges on ".$dsn["dbname"].".* to ".$dsn["username"]."@".$dsn["host"]." identified by \"".$dsn["password"]."\";\n";
                } else if($dbconf["type"] == "pgsql"){
                    if(@$dbconf["bin_path"]){
                        $bin_path = $dbconf["bin_path"];
                    } else {
                        $bin_path = "/usr/local/pgsql/bin";
                    }
                    echo "----> $bin_path/createuser --createdb --no-adduser --pwprompt ".$dsn["username"]."\n";
                    echo "--------> input password ".$dsn["password"]."\n";
                    echo "----> $bin_path/createdb -U ".$dsn["username"]." -E ".$dbconf["charset"]." ".$dsn["dbname"]."\n";
                }
                $this->status["dbConnectCheck"] = false;
                return false;
            }
            $this->db = $zdb_adapter;
            $this->status["dbConnectCheck"] = true;
            echo "db connection : ok\n";
        }
        return true;
    }
    public function tableCheck()
    {
        print "\n[table check]\n";

        if($this->status["dbConnectCheck"] == false)
        {
            echo "check db connection.\n";
            $this->status["tableCheck"] = false;
            return ;
        }
        if(!array_key_exists("logger", $this->registry)){
            $this->setupLogger();
        }
        try{
            // ユーザテーブルが存在して select できるかを確認
            $model = new Model_User();
            $res = $model->fetchAll();
            $model = new Model_Role();
            $res = $model->fetchAll();
            $model = new Model_UserRole();
            $res = $model->fetchAll();
        } catch(Exception $e){
            echo "----> user table: ng\n";
            echo "".$e->getMessage()."\n";

            // テーブルがないときは生成する
            $dbtype = $this->registry["config"]->db->type;
            if(!$dbtype){
                echo "--> There is no db type in config.ini\n";
                echo "    give up to create sys-tables.\n";
                return false;
            }
            //$skel_file = $this->platform_dir."/webapp/sql/".$dbtype.".skel.sql";
            $skel_file = $this->platform_dir."/webapp/modules/default/sql/".$dbtype.".sql";
            $meta_file = $this->platform_dir."/webapp/modules/default/sql/meta.xml";
            // sql ファイルがないけど meta.xml があればSQLファイルを生成する
            if(!file_exists($skel_file) && file_exists($meta_file)){
                echo "----> not found sql skelton file.\n";
                echo "      generate the file by meta.xml!\n";
                $processor = $this->platform_dir."/webapp/tool/metaprocessor.php";
                $cmd_response = shell_exec("php $processor $meta_file");
                if($cmd_response === null){ // shell_exec はエラー時に NULL を返す
                    return false;
                }
            }
            if(file_exists($skel_file)){
                echo "----> found sql skelton file($skel_file).\n";
                $skel = file_get_contents($skel_file);
                $db = $this->config->db;
                $dsn = $db->dsn;
                $skel = preg_replace("/<DBNAME>/", $dsn->dbname, $skel);
                $skel = preg_replace("/<DBUSER>/", $dsn->username, $skel);
                $skel = preg_replace("/<DBPASS>/", $dsn->password, $skel);
                $skel = preg_replace("/<PREFIX>/", $db->prefix, $skel);
                $skel = preg_replace("/<CHARSET>/", $db->charset, $skel);
                $tmpfile = "/tmp/tmp.sql";
                $fp = fopen($tmpfile, "w");
                if(!$fp){
                    echo "----> cannot open $tmpfile.\n";
                    $this->status["tableCheck"] = false;
                    return false;
                }
                if (fwrite($fp, $skel) === FALSE) {
                    echo "----> Cannot write to file ($tmpfile)";
                    $this->status["tableCheck"] = false;
                    return false;
                }
                fclose($fp);
                if($dbtype == "mysql"){
                    $mysql_cmd = "mysql -u ".$dsn->username." -p ".$dsn->dbname
                        ." --password=".$dsn->password." < /tmp/tmp.sql";
                    $res = shell_exec($mysql_cmd);
                } else if($dbtype == "pgsql"){
                    if(@$dbconf["bin_path"]){
                        $bin_path = $dbconf["bin_path"];
                    } else {
                        $bin_path = "/usr/local/pgsql/bin";
                    }
                    if(isset($dsn->port)){
                        $port = $dsn->port;
                    } else {
                        $port = 5432;
                    }
                    $psql_cmd = "$bin_path/psql -p $port -U ".$dsn->username." ".$dsn->dbname
                        ." < /tmp/tmp.sql";
                    $res = shell_exec($psql_cmd);
                }
                echo "----> create tables and insert initial data\n";
                //unlink($tmpfile); 
            } else {
                $this->status["tableCheck"] = false;
            }
        }
        $this->status["tableCheck"] = true;
        echo "----> user table: ok\n";
        return true;
    }
    public function defaultModTableCheck()
    {
        print "\n[default module table check]\n";

        if($this->status["dbConnectCheck"] == false)
        {
            echo "check db connection.\n";
            $this->status["tableCheck"] = false;
            return ;
        }

        // デフォルトモジュール名
        if($this->config->system){
            $defmod = $this->config->system->default_module;
        } else {
            $defmod = "default";
        }
        if(!$defmod){
            $defmod = "default";
        }
// --- 以下 ModAclAction の installCheck() と同じ
        // デフォルトモジュールの SQL ファイルパス
        $defmod_sqlfile = $this->platform_dir
            ."/webapp/modules/".$defmod."/sql/".
            $this->config->db->type.".sql";
        // DB 利用システムかの確認
        $sysconf = $this->config->system;
        if(isset($sysconf->use_db) && $sysconf->use_db == false){
            echo "----> this site don't use db.\n";
            return true;
        }
        // SQLファイル確認
        if(!file_exists($defmod_sqlfile)){
            echo "----> this module don't have sql file.\n";
            echo "file = $defmod_sqlfile\n";
            return true;
        }
        // DB 設定取得
        $prefix = $this->config->db->prefix;

        // 作成済みテーブル一覧取得
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        // DB 上の全てのテーブルリストを取得
// TODO:このへんにキャッシュをもたせたい
        if($this->config->db->type == "pgsql"){
        $sql = "SELECT
    TABLENAME
FROM
    PG_TABLES
WHERE
    NOT TABLENAME LIKE 'pg%'
    AND NOT SCHEMANAME = 'information_schema'
    AND SCHEMANAME = 'public'
ORDER BY
    TABLENAME";
            $res = $db->query($sql);
        } else {
            $res = $db->query("show tables");
        }
        $installed = array();
        foreach($res->fetchAll() as $k => $t){
            list($key, $table) = each($t);
            $installed[] = $table;
        }

        // モジュール SQL ファイルのテーブルが作成されているか確認
        $sqlfile_line = file($defmod_sqlfile);
        $not_installed = array();
        foreach($sqlfile_line as $line){
            if(!preg_match("/^create table (.+)\s*\(\s*$/i", $line, $m)){
                continue;
            }
            $_table = trim($m[1]);
            $_table = str_replace("<PREFIX>_", "", $_table);
            $_table = str_replace("<MODULE>_", "", $_table);

            $check_tbl = $prefix."_".$defmod."_".$_table;

            if(!in_array($check_tbl, $installed)){
                $not_installed[] = $check_tbl;
            }
        }

        // 当該モジュールのインストール済みテーブルを検出
        $pat = $prefix."_".$defmod."_";
        $mod_installed = array();
        foreach($installed as $tbl){
            if(preg_match("/^$pat/i", $tbl)){
                $mod_installed[] = $tbl;
            }
        }

        // 未作成のモジュールテーブルがあれば false
        $result = false;
        if(count($not_installed)){
            echo "----> default module table: ng\n";
            echo "--------> already created:\n";
            foreach($mod_installed as $item){
                echo "$item\n";
            }
            echo "--------> not created tablename:\n";
            foreach($not_installed as $item){
                echo "$item\n";
            }
            echo "\n----> Please create table by:\n";
            echo "sh ./alter.sh ../modules/$defmod/sql/"
                    .$this->config->db->type.".sql\n";
            $this->status["defaultModTableCheck"] = false;
            return false;
        }
        return true;
        $this->status["defaultModTableCheck"] = true;
        echo "----> default module table: ok\n";
        return true;
    }
    public function rootPathCheck()
    {
        print "\n[RootPath check]\n";
        if(!$this->config){
            print "----> skip: config is not defined\n";
            return;
        }
        $root_path = $this->config->site->root_path;
        $real_path = dirname(dirname(__FILE__))."/html";

        print "conf_path: $root_path";
        if($root_path != $real_path){
            print "\n";
            print "real_path: $real_path\n";
            print "----> edit config.ini by:\n";
            print "----> site.root_path = $real_path\n";
        } else {
            print "----> ok\n";
        }
    }
    public function showConfig()
    {
        if(!$this->config){
            return ;
        }
        print "\n";
        print "\n";
        print "configuration summary:\n";
        print "===[sysconf]==========\n";
        if(isset($this->config->system)){
            print_r($this->config->system->toArray());
        } else {
            print "no config: use system default action.\n";
        }
        print "===[siteconf]==========\n";
        $siteconf = $this->config->site->toArray();
        if(!array_key_exists("debug", $siteconf)){
            $siteconf["debug"] = 0;
        }
        print_r($siteconf);
        if($this->dbuse_flag){
            print "===[db]==========\n";
            print_r($this->config->db->toArray());
            print "===[acl]==========\n";
            print_r($this->config->acl->toArray());
        }
    }

    public function status()
    {
        return $this->status;
    }

    private function _getApplicationEnv()
    {
        $htaccess = $this->platform_dir."/html/.htaccess";
        if(!file_exists($htaccess)){
            echo "----> NOT found: .htaccess in public top directory\n";
            return false;
        }
        $grep_cmd = "grep '^SetEnv APPLICATION_ENV' $htaccess";
        $res = shell_exec("grep '^SetEnv APPLICATION_ENV' $htaccess");
        if(!$res){
            echo "----> NOT found: APPLICATION_ENV in .htaccess\n";
            return false;
        }
        preg_match("/^SetEnv APPLICATION_ENV ([a-z]+)/", $res, $m);
        return $m[1];
    }
}

// 書き込み許可を与えるディレクトリ
$dirs = array(
    "/webapp/temporary/templates_c",
    "/webapp/temporary/cache",
    "/webapp/log",
    "/webapp/uploads",
);

// システム設定ファイル
$files = array(
    "/webapp/config/config.ini",
);

$checker = new SysCheck();
$checker->dirPermCheck($dirs);
$checker->iniFileCheck($files);
$checker->loadConfigIni();
if($checker->checkDbUse()){
    if(!$checker->dbConnectCheck()) exit;
    if(!$checker->tableCheck()) exit;
    if(!$checker->defaultModTableCheck()) exit;
}
$checker->rootPathCheck();
$checker->showConfig();

//$status = $checker->status();
//print_r($status);

