<?php
/**
 * Manager
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
 * @package       Ao.webapp.Manager
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

abstract class Manager
{
    /**
     * 一般 Vo へ展開
     */
    protected function _assignCommonVo($row = array())
    {
        $vo = new Vo_Common();
        foreach($row as $key => $val){
            $vo->set($key, $val);
        }
        return $vo;
    }
    protected function _assignCommonVos($rows = array())
    {
        if(!$rows){
            return false;
        }

        //$backtraces = debug_backtrace();
        //print_r($backtraces[1]); //呼出もと

        $vos = array();
        foreach($rows as $row){
            /*
            $vo = new Vo_Common();
            foreach($row as $key => $val){
                $vo->set($key, $val);
            }
            */
            $vos[] = $this->_assignCommonVo($row);;
        }
        return $vos;
    }

    static public function getValidator(&$ctr, $_name, $is_pascal = false)
    {
        $mod  = Ao_Util_Str::toPascal($ctr->getRequest()->getModuleName());
        if($is_pascal == false){
            $name = Ao_Util_Str::toPascal($_name);
        } else {
            $name = $_name;
        }
        $class = $mod."_Form_Validator_".$name;
        return new $class($ctr);
    }
    static public function getForm(&$ctr, $_name, $is_pascal = false)
    {
        $mod  = Ao_Util_Str::toPascal($ctr->getRequest()->getModuleName());
        if($is_pascal == false){
            $name = Ao_Util_Str::toPascal($_name);
        } else {
            $name = $_name;
        }
        $class = $mod."_Form_".$name;
        return new $class($ctr);
    }
    static public function getManager($ctr, $_name, $is_pascal = false)
    {
        // $ctr に文字列が渡された場合はモジュール名と見なす
        if(is_string($ctr)){
            $mod = Ao_Util_Str::toPascal($ctr);
        }
        // Ao_Controller_Action のインスタンスである場合
        else if($ctr instanceof Ao_Controller_Action){
            $mod = Ao_Util_Str::toPascal($ctr->getRequest()->getModuleName());
        }
        // コントローラではあるがベースコントローラを識別できなかった場合
        else if(preg_match("/Controller$/", "Controller", get_class($ctr))) {
                $front = Zend_Controller_Front::getInstance();
                $mod = Ao_Util_Str::toPascal($front->getRequest()->getModuleName());
        }
        else {
            throw new Zend_Exception("invalid controller type");
        }

        if($is_pascal == false){
            $mng = Ao_Util_Str::toPascal($_name);
        } else {
            $mng = $_name;
        }

// 2011/11/18 テストコード：クラスファイルが見つからない時のトレース
// require_once とか Zend の __autoload() でクラスを生成すると Fatal Error でとまるので
// include_once でファイルの存在を確かめて無いことが分かったらトレース情報を出して止める。
        ob_start();
        $include_file = $mod."/Manager/".$mng.".php";
        $inc_res = include_once($include_file);
        $content = ob_get_contents();
        ob_end_clean();
        if($inc_res == false){
            $msg = "not found manager file: $include_file";
            Ao_Util_Debug::trace(debug_backtrace(), $msg);
            //trigger_error($content, E_USER_ERROR);
            exit;
        }
// -- 2011/11/18
        $class = $mod."_Manager_".$mng;
        return new $class($ctr);
    }
    static public function getModel($_modname, $_tblname, $is_pascal = false)
    {
        if(is_string($_modname)){
            if($is_pascal == false){
                $mod = Ao_Util_Str::toPascal($_modname);
            } else {
                $mod = $_modname;
            }
        } else { // 多分コントローラだと過程
            $mod = Ao_Util_Str::toPascal($_modname->getRequest()->getModuleName());
        }
        if($is_pascal == false){
            $tbl = Ao_Util_Str::toPascal($_tblname);
        } else {
            $tbl = $_tblname;
        }
        $class = $mod."_Model_".$tbl;
        return new $class();
    }

    static public function trace()
    {
        $tr = debug_backtrace();
        foreach($tr as $i => $r){
            if(isset($r["object"])){
                $r["object"] = get_class($r["object"]);
            }
            if(is_array($r["args"])){
                foreach($r["args"] as $j => $a){
                    if(is_object($a)){
                        $r["args"][$j] = get_class($a)." Obj";
                    }
                    else if(is_string($a)){
                        $r["args"][$j] = "\"".$a."\"";
                    }
                    else if(is_array($a)){
                        ob_start();
                        print_r($a);
                        $content = ob_get_contents();
                        ob_end_clean();
                        $r["args"][$j] = "Array=>\n".$content."\n";
                    }
                }
            }
            $tr[$i] = $r;
        }
        print_r($tr);
        exit;
    }


    public function beginTransaction()
    {
        $reg = Zend_Registry::getInstance();
        $adp = $reg["zdb_adapter"];
        $adp->beginTransaction();
        Zend_Registry::get("logger")->debug("begin transaction");
    }
    public function rollback()
    {
        $reg = Zend_Registry::getInstance();
        $adp = $reg["zdb_adapter"];
        $adp->rollback();
        Zend_Registry::get("logger")->debug("rollback");
    }
    public function commit()
    {
        $reg = Zend_Registry::getInstance();
        $adp = $reg["zdb_adapter"];
        $adp->commit();
        Zend_Registry::get("logger")->debug("commit");
    }
}
