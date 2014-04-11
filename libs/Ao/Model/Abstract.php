<?php
/**
 * Base Dao Class
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
 * @package       Ao.Model
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Ao_Model_Abstract extends Zend_Db_Table_Abstract
{
    const FETCH_AS_VO = 0;
    const FETCH_AS_ARRAY = 1;

    protected $_ip;
    protected $_info = array();
    private $log;
    private $_modname;
    private $_tblname;
    private $_config;

    public function __construct($_modname = null, $_tblname = null)
    {
        parent::__construct();
        $this->_ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "command line";
        $registry = Zend_Registry::getInstance();
        $this->_config = $registry["config"];
        $this->log = $registry["logger"];

        $prefix = $registry["config"]->db->prefix;
        if($_tblname && $_modname){ // モジュール名＋テーブル名指定
            $this->_name = $prefix."_".$_modname."_".$_tblname;
        }
        else if($_modname ){        // モジュール名のみ指定
            $classname = get_class($this);
            if(preg_match("/^([A-Z][a-z0-9]+)_Model_(.+)/", $classname, $m)){
                //$module = strtolower($m[1]);
                $module = $_modname;
                $name = $m[2];
                if(class_exists("Ao_Util_Str")){
                    $name = Ao_Util_Str::toSnake($name);
                }
            }
            $this->_name = $prefix."_".$module."_".$name;
        }
    }
    protected function _setupPrimaryKey()
    {
        try{
            parent::_setupPrimaryKey();
        } catch(Zend_Db_Table_Exception $e) {
            $msg = $this->_name ." table: ".$e->getMessage();
            throw new Zend_Db_Table_Exception($msg);
        }
    }
    protected function _setupTableName()
    {
        $registry = Zend_Registry::getInstance();
        $conf = $registry["config"];

        if(!$this->_name){
            //$classname = strtolower(get_class($this));
            $classname = get_class($this);
            if(preg_match("/^Model_.+/", $classname)){
                $name = preg_replace("/^Model_(.+)$/", "\\1", $classname); 
                if(class_exists("Ao_Util_Str")){
                    $name = Ao_Util_Str::toSnake($name);
                }
                $this->_modname = null;
                $this->_tblname = strtolower($name);
            }
            else if(preg_match("/^([A-Z][a-z0-9]+)_Model_(.+)/", $classname, $m)){
                // モジュールライブラリ名は先頭がモジュールディレクトリ名で始まる
                // Model の場合は [モジュール名]_Model_[テーブル名] となる
                $module = strtolower($m[1]);
                $name = $m[2];
                if(class_exists("Ao_Util_Str")){
                    $name = Ao_Util_Str::toSnake($name);
                }

                $this->_modname = strtolower($module);
                $this->_tblname = strtolower($name);

                // プレフィックス無しのテーブル名
                $name = $module."_".$name;
            }

            if(isset($conf->db->prefix) 
                && $conf->db->prefix != ""
            ){
                $name = (string)$conf->db->prefix."_".$name; 
            }
            $this->_name = $name;
        }
        parent::_setupTableName();
    }
    public function fieldNames()
    {
        if(!$this->_info){
            $this->_info = $this->info();
        }
        return $this->_info["cols"];
    }
    public function name($toLower = false)
    {
        return $this->_name;
    }
    public function primaryKey()
    {
        if(!$this->_info){
            $this->_info = $this->info();
        }
        return current($this->_info["primary"]);
    }
    public function getVo($data = array())
    {
        //$this_class = get_class($this);
        //if(preg_match("/^Model_.+/", $this_class)){
        //    $name = preg_replace("/^Model_(.+)$/", "\\1", $this_class);
        //    $class = "Vo_".$name;
        //}
        //else if(preg_match("/^([A-Z][a-z0-9]+)_Model_(.+)$/", $this_class, $m)){
        //    $mod = $m[1];
        //    $name = $m[2];
        //    $class = $mod."_Vo_".$name;
        //}
        if($this->_modname){
            $class = Ao_Util_Str::toPascal($this->_modname)."_Vo_".Ao_Util_Str::toPascal($this->_tblname);
        } else {
            //$class = "Vo_".ucfirst($this->_tblname);
            $class = "Vo_".Ao_Util_Str::toPascal($this->_tblname);
        }
        $vo = new $class();
        $cols = $this->fieldNames();
        foreach($cols as $c){
            if(isset($data[$c])){
                $vo->set($c, $data[$c]);
            } else {
                $vo->set($c, null);
            }
        }
        return $vo;
    }
    public function fetchAll($select = null, $return_array = false)
    {
        $query_log_registry = Zend_Registry::get("query_log");
        $query_log_array = $query_log_registry["log"];
        $cacheModelName = get_class($this);
        if(is_string($select)){
            $query_hash = sha1(get_class($this).$select);
        } else if(!is_string($select) && $select != "") {
            $query_hash = sha1(get_class($this).$select->__toString());
        } else {
            $query_hash = sha1(get_class($this));
        }
        $queryCache= Zend_Registry::get("queryCache");
        if(array_key_exists($cacheModelName, $queryCache) 
            && array_key_exists($query_hash, $queryCache[$cacheModelName])
        ){
            if($select instanceof Zend_Db_Select){
                $this->log->debug($this->_ip.", ".get_class($this).", fetchAll() return from queryCache;", Zend_Log::DEBUG);
                $this->log->debug($this->_ip.", "."> cache query = ".$select->__toString(), Zend_Log::DEBUG);
                $query_log_array[] = "use cache = ".$select->__toString();
            } else if(is_string($select)){
                $this->log->debug($this->_ip.", ".get_class($this).", fetchAll() return from queryCache;", Zend_Log::DEBUG);
                $this->log->debug($this->_ip.", "."> cache query = ".$select, Zend_Log::DEBUG);
                $query_log_array[] = "use cache = ".$select;
            } else {
                $this->log->debug($this->_ip.", ".get_class($this).", fetchAll() return from queryCache;", Zend_Log::DEBUG);
                $query_log_array[] = "use cache";
            }
            $rowset =& $queryCache[$cacheModelName][$query_hash];
        } else {
            try{
                $rowset = parent::fetchAll($select);
                if(isset($this->_config->site->debug)){
                    $query = $this->_db->getProfiler()->getLastQueryProfile();
                    if($query){
                        $this->log->debug($this->_ip.", ".get_class($this).", ".$query->getQuery(), Zend_Log::DEBUG);
                        $query_log_array[] = $query->getQuery();
                    } else if($select) {
                        $this->log->debug($this->_ip.", ".get_class($this).", \$select->__toString() == ".$select->__toString(), Zend_Log::DEBUG);
                        $query_log_array[] = $select->__toString();
                    } else {
                        $this->log->debug($this->_ip.", ".get_class($this).", exec fetchAll();", Zend_Log::DEBUG);
                    }
                }

            } catch (Zend_Exception $e) {

                //$query = $this->_db->getProfiler()->getLastQueryProfile();
                if($select){
                    if( $select instanceof Zend_Db_Select ){
                        $this->log->err($this->_ip.", "."===>".$select->__toString());
                    }
                    $this->log->err($this->_ip.", ".$e->getMessage());
                } else {
                    $this->log->err($this->_ip.", ".$e->getMessage());
                }
                throw $e;

            }
            $tmpCache = Zend_Registry::get("queryCache");
            $tmpCache[$cacheModelName][$query_hash] = $rowset;
            Zend_Registry::set("queryCache",  $tmpCache);
        }

        $query_log_registry["log"] = $query_log_array;
        Zend_Registry::set("query_log", $query_log_registry);

        $rows = array();
        if(count($rowset)){
            $rowsetArray = $rowset->toArray();
            if($return_array){
                return $rowsetArray;
            }
            foreach($rowsetArray as $row){
                $rows[] = $this->getVo($row);
            }
        }
        return $rows;
    }
    public function prepare($data)
    {
        $cols = $this->fieldNames();
        $res = array();
        foreach($cols as $col){
            $res[$col] = isset($data[$col]) ? $data[$col] : null;
        }
        return $res;
    }
    public function save($_data = array(), $updateWhere = null)
    {
        $registry = Zend_Registry::getInstance();
        $logger = $registry["logger"];
        if(!$_data){
            return false;
        }

        if($_data instanceof Ao_Vo_Abstract){
            $data = $_data->toArray();
        } else {
            $data = $this->prepare($_data);
        }

/* PostgreSQL の型チェックが厳しいので */
        if(array_key_exists("metadata", $this->_info)){
        foreach($this->_info["metadata"] as $col => $definition){
            $type = $definition["DATA_TYPE"];
            if(preg_match("/^int/", $type)){
                if(substr($col, -2) == "id" && $definition["PRIMARY"] && $data[$col] == ""){
                    $data[$col] = null;
                } else {
                    $data[$col] = (int)$data[$col];
                }
            }
            else if(preg_match("/^varchar/", $type) || $type == "text"){
                $data[$col] = (string)$data[$col];
            }
            else if(preg_match("/^float/", $type) && $data[$col] == ""){
                $data[$col] = null;
            }
        }
        }
/* /PostgreSQL の型チェックが厳しいので */

        $pkey = $this->primaryKey();
        $cacheModelName = get_class($this);
        if(isset($data[$pkey]) && $data[$pkey] !== ""){
            // update 用条件文が指定されていたらそちらを優先して使う
            // 未指定の場合は主キーの値を参照する
            if($updateWhere){
                $select = $this->select()->where($updateWhere);
            } else {
                $select = $this->select()->where($pkey." = ?", $data[$pkey]);
            }
            $row = $this->fetchAll($select);
            if($row){
                if(in_array("created", $this->fieldNames()) && $data["created"] === ""){
                    $data["created"] = date("Y-m-d H:i:s");
                }
                if(in_array("modified", $this->fieldNames())){
                    $data["modified"] = date("Y-m-d H:i:s");
                }
                if(in_array("delete_flag", $this->fieldNames()) && $data["delete_flag"] === ""){
                    $data["delete_flag"] = 0;
                }
                if($updateWhere){
                    $where = $updateWhere;
                } else {
                    $where = $this->getAdapter()->quoteInto($pkey." = ?", $data[$pkey]);
                }
                try {
                    $res = parent::update($data, $where);
                    $res = $data[$pkey];
                    $query = $this->_db->getProfiler()->getLastQueryProfile();
                    $msg = $query->getQuery().", array('".implode("','", $data)."')";
                    $logger->info($msg);
                    // 当該モデルクエリキャッシュをクリア
                    $queryCache= Zend_Registry::get("queryCache");
                    unset($queryCache[$cacheModelName]);
                    Zend_Registry::set("queryCache", $queryCache);
                    $this->log->debug($this->_ip.", ".$cacheModelName.", clear queryCache;", Zend_Log::DEBUG);
                } catch(Zend_Exception $e) {
                    $query = $this->_db->getProfiler()->getLastQueryProfile();
                    $msg = $this->_ip.", ".$query->getQuery().", array('".implode("','", $data)."')";
                    $logger->err($msg);
                    throw $e;
                }
            } else {
                if(in_array("created", $this->fieldNames())){
                    $data["created"] = date("Y-m-d H:i:s");
                }
                if(in_array("modifled", $this->fieldNames())){
                    $data["modified"] = $data["created"];
                }
                if(in_array("delete_flag", $this->fieldNames())){
                    $data["delete_flag"] = 0;
                }
                try {
                    $res = parent::insert($data);
                    $query = $this->_db->getProfiler()->getLastQueryProfile();
                    $msg = $query->getQuery().", array('".implode("','", $data)."')";
                    $logger->info($msg);
                    // 当該モデルクエリキャッシュをクリア
                    $queryCache= Zend_Registry::get("queryCache");
                    unset($queryCache[$cacheModelName]);
                    Zend_Registry::set("queryCache", $queryCache);
                    $this->log->debug($this->_ip.", ".$cacheModelName.", clear queryCache;", Zend_Log::DEBUG);
                } catch(Zend_Exception $e) {
                    $query = $this->_db->getProfiler()->getLastQueryProfile();
                    $msg = $this->_ip.", ".$query->getQuery().", array('".implode("','", $data)."')";
                    ob_start(); print_r($data); $content=ob_get_contents(); ob_end_clean();
                    $msg .= "\n".$content;
                    $logger->err($msg);
                    throw $e;
                }
            }
        } else {
            if(in_array("created", $this->fieldNames())){
                $data["created"] = date("Y-m-d H:i:s");
            }
            if(in_array("modifled", $this->fieldNames())){
                $data["modified"] = $data["created"];
            }
            if(in_array("delete_flag", $this->fieldNames())){
                $data["delete_flag"] = 0;
            }
            try{
                $res = parent::insert($data);
                $query = $this->_db->getProfiler()->getLastQueryProfile();
                $msg = $query->getQuery().", array('".implode("','", $data)."')";
                $logger->info($msg);
                // 当該モデルクエリキャッシュをクリア
                $queryCache= Zend_Registry::get("queryCache");
                unset($queryCache[$cacheModelName]);
                Zend_Registry::set("queryCache", $queryCache);
                $this->log->debug($this->_ip.", ".$cacheModelName.", clear queryCache;", Zend_Log::DEBUG);
            } catch(Zend_Exception $e) {
                $query = $this->_db->getProfiler()->getLastQueryProfile();
                $msg = $this->_ip.", ".$query->getQuery().", array('".implode("','", $data)."')";
                ob_start(); print_r($data); $content=ob_get_contents(); ob_end_clean();
                $msg .= "\n".$content;
                $logger->err($msg);
                throw $e;
            }
        }
        return $res;
    }
    public function del($id = null)
    {
        $where = $this->getAdapter()->quoteInto($this->primaryKey()." = ?", $id);
        try{
            $this->delete($where);
            if(isset($this->_config->site->debug)){
                $query = $this->_db->getProfiler()->getLastQueryProfile();
                $this->log->debug($this->_ip.", ".get_class($this).", ".$query->getQuery(), Zend_Log::DEBUG);
            }
        } catch(Zend_Exception $e) {
            if(isset($this->_config->site->debug)){
                $query = $this->_db->getProfiler()->getLastQueryProfile();
                $this->log->log($this->_ip.", ".$query->getQuery(), Zend_Log::ERR);
            }
            throw $e;
        }
    }
    public function truncateTable()
    {
        $adp = $this->getAdapter();
        $sql = "TRUNCATE TABLE ".$this->name()."";
        $res = $adp->query($sql);

        if($this->_config->db->type == "pgsql"){
            $pkey = $this->primaryKey();
            $seq_name = $this->name()."_".$pkey."_seq";
            $sql = "select setval('".$seq_name."', 1)";
            $res = $adp->query($sql);
        }
        return $res;
    }
    public function query($sql)
    {
        Zend_Registry::get("logger")->debug($this->_ip.", ".$sql);
        $this->getAdapter()->query($sql);
    }

    public function clearMetadataCache($table_name = null)
    {
        // see: libs/Zend/Db/Table/Abstract.php
        if(!$table_name){
            $table_name = $this->_name;
        }
        //get db configuration
        $dbConfig = $this->_db->getConfig();

        $port = isset($dbConfig['options']['port'])
              ? ':'.$dbConfig['options']['port']
              : (isset($dbConfig['port'])
              ? ':'.$dbConfig['port']
              : null);

        $host = isset($dbConfig['options']['host'])
              ? ':'.$dbConfig['options']['host']
              : (isset($dbConfig['host'])
              ? ':'.$dbConfig['host']
              : null);

        // Define the cache identifier where the metadata are saved
        $cacheId = md5( // port:host/dbname:schema.table (based on availabilty)
                $port . $host . '/'. $dbConfig['dbname'] . ':'
              . $this->_schema. '.' . $table_name
        );

        $cache = $this->getDefaultMetadataCache();
        $cache->remove($cacheId);
        return $cacheId;
    }
}
