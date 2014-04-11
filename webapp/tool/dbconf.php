<?php
/**
 * Generate Model,Vo,Form,Validator  class
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

if(phpversion() >= "5.1.0"){
    date_default_timezone_set("Asia/Tokyo");
}

require_once "CodeTool.php";

class MVFV {

    private $_mod;
    private $_tbl;
    private $_type;
    
    public function __construct($mod, $tbl, $type)
    {
        $this->_mod = $mod;
        $this->_tbl = $tbl;
        $this->_type = $type;
    }
    public function execute()
    {
        $target_file = "../config/config.ini";
        $target_custom = "../config/custom/config.ini";
        if(file_exists($target_custom)){
            $target_file = $target_custom;
        }

        $tool = new CodeTool();
        $tool->setupEnv();
        $target_section = $tool->getApplicationEnv();
        $tool->loadConfig($target_file, $target_section);
        $tool->setupDbAdapter();

        $module_name = $this->_mod;
        $table_name = $this->_tbl;

        $ModuleName = Ao_Util_Str::toPascal($module_name, "-");
        $TableName = Ao_Util_Str::toPascal($table_name);

        //print $ModuleName."\n";
        //print $TableName;print "\n";exit;

        //$tableInfo = $tool->getTableInfo($table_name, $module_name);
        $tableInfo = $tool->getTableInfoByMeta($table_name, $module_name);
        //print_r($tableInfo);
        //print_r($tableInfo2);exit;

        $smarty = $tool->initSmarty();
        $smarty->assign("module_name", $module_name);
        $smarty->assign("ModuleName", $ModuleName);
        $smarty->assign("table_name", $table_name);
        $smarty->assign("TableName", $TableName);
        $smarty->assign("tableInfo", $tableInfo);

        // Template を指定
        if($this->_type == "Form"){
            $tpl = "Form/Skelton.php";
        } else if($this->_type == "Validator") {
            $tpl = "Form/Validator/Skelton.php";
        } else if($this->_type == "Controller") {
            $tpl = "controller/SkeltonController.php";
        }

        $script = $smarty->fetch($tpl);
        print $script;
    }
}
class Ctr{

    private $_mod;
    private $_tbl;
    private $_isadmin;
    
    public function __construct($mod, $tbl, $isadmin = false)
    {
        $this->_mod = $mod;
        $this->_tbl = $tbl;
        $this->_isadmin = $isadmin;
    }
    public function execute()
    {
        $target_file = "../config/config.ini";
        $target_custom = "../config/custom/config.ini";
        if(file_exists($target_custom)){
            $target_file = $target_custom;
        }

        $tool = new CodeTool();
        $tool->setupEnv();
        $target_section = $tool->getApplicationEnv();
        $tool->loadConfig($target_file, $target_section);
        $tool->setupDbAdapter();

        $module_name = $this->_mod;
        $table_name = $this->_tbl;

        $ModuleName = Ao_Util_Str::toPascal($module_name, "-");
        $TableName = Ao_Util_Str::toPascal($table_name);

        //print $ModuleName."\n";
        //print $TableName;print "\n";exit;

        //$tableInfo = $tool->getTableInfo($table_name, $module_name);
        $tableInfo = $tool->getTableInfoByMeta($table_name, $module_name);
        //print_r($tableInfo);exit;

        //$conf["left_delimiter"] = "<{--";
        //$conf["right_delimiter"] = "--}>";
        $conf = array();
        $smarty = $tool->initSmarty($conf);
        $smarty->assign("module_name", $module_name);
        $smarty->assign("ModuleName", $ModuleName);
        $smarty->assign("table_name", $table_name);
        $smarty->assign("TableName", $TableName);
        $smarty->assign("tableInfo", $tableInfo);
        $smarty->assign("isAdmin", $this->_isadmin);

        // Template を指定
        $tpl = "controller/SkeltonController.php";

        $script = $smarty->fetch($tpl);
        print $script;
    }
}
class View {

    private $_mod;
    private $_tbl;
    private $_isadmin;
    
    public function __construct($mod, $tbl, $isadmin = false, $view = "index")
    {
        $this->_mod = $mod;
        $this->_tbl = $tbl;
        $this->_isadmin = $isadmin;
        $this->_view = $view;
    }
    public function execute()
    {
        $target_file = "../config/config.ini";
        $target_custom = "../config/custom/config.ini";
        if(file_exists($target_custom)){
            $target_file = $target_custom;
        }


        $tool = new CodeTool();
        $tool->setupEnv();
        $target_section = $tool->getApplicationEnv();
        $tool->loadConfig($target_file, $target_section);
        $tool->setupDbAdapter();

        $module_name = $this->_mod;
        $table_name = $this->_tbl;

        $ModuleName = Ao_Util_Str::toPascal($module_name, "-");
        $TableName = Ao_Util_Str::toPascal($table_name);

        //print $ModuleName."\n";
        //print $TableName;print "\n";exit;

        //$tableInfo = $tool->getTableInfo($table_name, $module_name);
        $tableInfo = $tool->getTableInfoByMeta($table_name, $module_name);
        //print_r($tableInfo);exit;

        $conf["left_delimiter"] = "<{--";
        $conf["right_delimiter"] = "--}>";
        $smarty = $tool->initSmarty($conf);
        $smarty->assign("module_name", $module_name);
        $smarty->assign("ModuleName", $ModuleName);
        $smarty->assign("table_name", $table_name);
        $smarty->assign("TableName", $TableName);
        $smarty->assign("tableInfo", $tableInfo);
        $smarty->assign("isAdmin", $this->_isadmin);

        // Template を指定
        if($this->_isadmin){
            $tpl = "templates/admin/".$this->_view.".html";
        } else {
            $tpl = "templates/index/".$this->_view.".html";
        }

        $script = $smarty->fetch($tpl);
        print $script;
    }
}
class MNG {
    private $_mod;
    private $_mng;
    private $_usemods;
    public function __construct($mod, $mng, $usemods)
    {
        $this->_mod = $mod;
        $this->_mng = $mng;
        $this->_usemods = $usemods;
    }
    public function execute()
    {
        $target_file = "../config/config.ini";
        $target_custom = "../config/custom/config.ini";
        if(file_exists($target_custom)){
            $target_file = $target_custom;
        }


        $tool = new CodeTool();
        $tool->setupEnv();
        $target_section = $tool->getApplicationEnv();
        $tool->loadConfig($target_file, $target_section);

        $ModuleName = Ao_Util_Str::toPascal($this->_mod, "-");
        $ManagerName = Ao_Util_Str::toPascal($this->_mng);

        $_tmp = explode(":", $this->_usemods);
        $UseModels = array();
        foreach($_tmp as $tmp){
            $parts = explode("=", $tmp);
            $modModule = Ao_Util_Str::toPascal($parts[0], "-");
            $modTable = Ao_Util_Str::toPascal($parts[1]);
            $UseModels[] = array(
                "tableName" => $modTable,
                "className" => $modModule."_Model_".$modTable
            );
        }

        $smarty = $tool->initSmarty();
        $smarty->assign("ModuleName", $ModuleName);
        $smarty->assign("ManagerName", $ManagerName);
        $smarty->assign("UseModels", $UseModels);

        // Template を指定
        $tpl = "Manager/Skelton.php";

        $script = $smarty->fetch($tpl);
        print $script;
    }
}

// チェック
$target = array("Form", "Validator", "Manager", "Controller", "View");
if(count($argv) < 4 || !in_array($argv[3], $target)){
    print_r($argv);
    print "Usage: ".$argv[0]." <module_name> <table_name> [Form|Validator]\n";
    print "Usage: ".$argv[0]." <module_name> <manager_name> Manager <mod1=tbl1:mod2=tbl2:...>\n";
    print "Usage: ".$argv[0]." <module_name> <table_name> Controller [0|1]\n";
    exit(1);
}
//if($argv[3] == "Controller"){
//}
if($argv[3] == "Manager" && count($argv) < 5){
    print "Usage: ".$argv[0]." <module_name> <manager_name> Manager <mod1=tbl1:mod2=tbl2:...>\n";
    exit(1);
}

// 実行
if(in_array($argv[3], array("Form", "Validator"))){
    $obj = new MVFV($argv[1], $argv[2], $argv[3]);
} else if($argv[3] == "Controller"){
    if(!isset($argv[4])){
        $argv[4] = 0;
    }
    $obj = new Ctr($argv[1], $argv[2], $argv[4]);
} else if($argv[3] == "View"){
    $obj = new View($argv[1], $argv[2], $argv[4], $argv[5]);
} else if($argv[3] == "Manager"){
    $obj = new MNG($argv[1], $argv[2], $argv[4]);
}
$obj->execute();

