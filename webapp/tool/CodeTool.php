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
class CodeTool {

    protected $syslib_dir;
    protected $webapp_dir;
    protected $htdocs_dir;
    protected $modlib_dirs; // array
    protected $config;
    protected $zdb_adapter;
    protected $exdb_adapter;
    protected $registry; // for using Model_Abstract class

    public function __construct($ldir = null, $adir = null, $hdir = null){
        $this_dir = dirname(__FILE__);
        if(!$ldir) $ldir = realpath("$this_dir/../../libs"); // system lib dir
        if(!$adir) $adir = realpath("$this_dir/..");         // webapp dir
        if(!$hdir) $hdir = realpath("$this_dir/../../html"); // htdocs dir
        $this->setSyslibDir($ldir);
        $this->setWebappDir($adir);
        $this->setHtdocsDir($hdir);
    }

    public function setSyslibDir($dir)
    {
        $this->syslib_dir = realpath($dir);
    }
    public function setWebappDir($dir)
    {
        $this->webapp_dir = $dir;
    }
    public function setHtdocsDir($dir)
    {
        $this->htdocs_dir = $dir;
    }
    public function setModlibDirs($dirs)
    {
        $this->modlib_dirs = $dirs;
    }
    public function setupPath()
    {
        self::setupEnv();
    }
    public function setupEnv()
    {
        if(!isset($this->syslib_dir)){
            $this->syslib_dir = "../../libs";
        }
        $syslib = $this->syslib_dir;
        $iniset_path[] = $this->syslib_dir;
        $iniset_path[] = $this->syslib_dir."/smarty";
        $iniset_path[] = $this->webapp_dir."/libs";
        $iniset_path[] = $this->webapp_dir."/extlibs";
        if(isset($this->modlib_dirs) && is_array($this->modlib_dirs)){
            foreach($this->modlib_dirs as $dir){
                $dup_libpath = $dir."/default"; // 複製対応時のディレクトリがあれば追加
                if(file_exists($dup_libpath)){
                    $iniset_path[] = $dup_libpath;
                }
                if(file_exists($dir)){
                    $iniset_path[] = $dir;
                }
            }
        } else if (isset($this->modlib_dirs)) {
            $iniset_path[] = $this->modlib_dirs;
        }
        ini_set("include_path", implode(":", $iniset_path));
        require_once 'Zend/Loader/Autoloader.php';
        Zend_Loader_Autoloader::getInstance()
            ->setFallbackAutoloader(true)
            ->pushAutoloader(NULL, 'Smarty_' );

        // クエリキャッシュ初期化
        $registry = Zend_Registry::getInstance();
        $registry["queryCache"] = array("data"=>array());
    }
    public function loadConfig($target_file, $target_section, $reg_target = "config")
    {
        $info = pathinfo($target_file);
        $custom_file = $info["dirname"]."/custom/".$info["basename"];
        $this->registry = Zend_Registry::getInstance();
        if(file_exists($custom_file)){
            $target_file = $custom_file;
        }
        else if(!file_exists($target_file)){
            throw new Zend_Exception("no config file: $target_file");
        }
        $this->registry[$reg_target] = new Zend_Config_Ini($target_file, $target_section);
        return $this;
    }
    public function getConfig()
    {
        return $this->registry["config"];
    }
    public function setupDbAdapter()
    {
        $db = $this->registry["config"]->db->toArray();
        $dsn = $db["dsn"];
        $opt_arr = array(
            "host" => $dsn["host"],
            "username" => $dsn["username"],
            "password" => $dsn["password"],
            "dbname" => $dsn["dbname"],
            "charset" => $db["charset"],
        );
        if(array_key_exists("port", $dsn)){
            $opt_arr["port"] = $dsn["port"];
        }
        try {
            $this->zdb_adapter = Zend_Db::factory($dsn["driver"], $opt_arr);
            $con = $this->zdb_adapter->getConnection();
            if(is_null($con)){
                print "getConnection が NULL を返した";
                exit;
            }
            $this->zdb_adapter->getProfiler()->setEnabled(true);
            Zend_Db_Table_Abstract::setDefaultAdapter($this->zdb_adapter);
        }
        catch(Exception $e){
            print $e->getMessage();
            exit;
        }
    }
    public function setupUserDbAdapter($dbconf, $adapter_name = "extdb_adapter")
    {
        $driver = $dbconf["driver"];
        $opt_arr = array(
            "host"     => $dbconf["host"],
            "username" => $dbconf["username"],
            "password" => $dbconf["password"],
            "dbname"   => $dbconf["dbname"],
            "charset"  => $dbconf["charset"],
        );
        if(array_key_exists("port", $opt_arr)){
            $opt_arr["port"] = $dsn["port"];
        }
        try {
            $this->exdb_adapter = Zend_Db::factory($driver, $opt_arr);
            $con = $this->exdb_adapter->getConnection();
            if(is_null($con)){
                print "getConnection が NULL を返した:". __METHOD__;
                exit;
            }
            $this->exdb_adapter->getProfiler()->setEnabled(true);
            //Zend_Db_Table_Abstract::setDefaultAdapter($this->exdb_adapter);
            Zend_Registry::set($adapter_name, $this->exdb_adapter);
        }
        catch(Exception $e){
            print $e->getMessage();
            exit;
        }
    }
    public function setupLooger($file = "codetool.log")
    {
        if(!isset($this->registry["logger"])){
            $log_path = $this->webapp_dir."/log/".$file;
            $logger = new Zend_Log();
            $writer = new Zend_Log_Writer_Stream($log_path);
            $logger->addWriter($writer);
            $this->registry["logger"] = $logger;
            $this->registry["query_log"] = array("start mypid" => getmypid(), "log" => array());
        }
    }
    public function setupModulePath()
    {
        $dirs = glob($this->webapp_dir."/modules/*");
        $inc_path = ini_get("include_path");
        $mod_path = array();
        foreach($dirs as $dir){
            if(is_dir($dir)){
                $mod_path[] = $dir."/libs";
                $mod_path[] = $dir."/libs/default";
            }
        }
        ini_set("include_path", $inc_path.":".implode(":", $mod_path));
    }
    public function getTableInfo($tbl, $mod = null)
    {
        $tablename = null;
        if($this->registry["config"]->db->prefix){
            $tablename .= (string)$this->registry["config"]->db->prefix;
        }
        if($mod){
            if($tablename){
                $tablename .= "_".$mod;
            } else{
                $tablename = $mod;
            }
        }
        if($tablename){
            $tablename .= "_".$tbl;
        } else {
            $tablename = $tbl;
        }
        return $this->zdb_adapter->describeTable($tablename);
    }
    public function getTableInfoByMeta($tbl, $mod)
    {
        /*
        $file = "../modules/".$mod."/sql/meta.csv";
        if(!file_exists($file)){
            // もしメタファイルが見つからない場合は、DBを直接参照する
            print "not found metafile: $file\n";
            print "---> try: CodeTool::getTableInfo()\n";
            return $this->getTableInfo($tbl, $mod);
        }

        require_once "Meta.php";
        $meta = new Meta();
        $meta->load($file);
        */
        $file = "../modules/".$mod."/sql/meta.xml";
        if(!file_exists($file)){
            // もしメタファイルが見つからない場合は、DBを直接参照する
            print "not found metafile: $file\n";
            print "---> try: CodeTool::getTableInfo()\n";
            return $this->getTableInfo($tbl, $mod);
        }
        require_once "MetaProcessor.php";
        $meta = new MetaProcessor();
        $meta->load($file);
        $meta->parseMod(); 
        $meta->parseDb();
        $meta->parseTableCsv();
        $tableInfo = $meta->tables();
        return $tableInfo[$tbl]["columns"];
    }
    public function initSmarty($tmpConf = array())
    {
        require_once "Smarty.class.php";
        //$tool_dir = dirname(__FILE__);
        $tool_dir = $this->webapp_dir."/tool";
        $smartyConf = $this->registry["config"]->smarty->toArray();
        $smartyConf["template_dir"] = $tool_dir."/skel/";
        $smartyConf["cache_dir"] = $tool_dir . "/.cache/";
        $smartyConf["compile_dir"] = $tool_dir . "/.templates_c/";
        if($tmpConf){
            $smartyConf["left_delimiter"] = $tmpConf["left_delimiter"];
            $smartyConf["right_delimiter"] = $tmpConf["right_delimiter"];
        } else {
            $smartyConf["left_delimiter"] = "<{";
            $smartyConf["right_delimiter"] = "}>";
        }
        if (!is_dir($smartyConf["cache_dir"])
            || !is_writable($smartyConf["cache_dir"])
        ) {
            die('smartyキャッシュディレクトリに書き込みできません。');
        }
        if (!is_dir($smartyConf['compile_dir'])
            || !is_writable($smartyConf['compile_dir'])
        ) {
            die('smartyコンパイルディレクトリに書き込みできません。');
        }

        // config.ini の site セクションを埋め込む
        return new Ao_Util_Mysmarty($smartyConf);
    }
    public function getApplicationEnv($htaccess = null)
    {
        $htaccess = $this->htdocs_dir."/.htaccess";
        $key = "/^SetEnv APPLICATION_ENV\s(production|development|debug|stage)/";
        $contents = file($htaccess);
        $target = array();
        foreach($contents as $line){
            if(preg_match($key, $line, $m)){
                $target = $m;
            }
        }
        if($target){
            return $target[1];
        } else {
            throw new Zend_Exception("not found environtment in .htaccess");
        }
    }

    public function loadXml($filepath)
    {
        $xml = new Ao_Util_Xml();
        return $xml->load($filepath);
    }
}

