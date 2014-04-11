<?php
/**
 * Modperm Manager
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

class Manager_Modperm extends Manager
{
    var $controller;
    var $request;

    public function __construct(&$controller)
    {
        $this->registry = Zend_Registry::getInstance();
        $this->controller = $controller;
        $this->request = $this->controller->getRequest();
        $this->auth = new Zend_Session_Namespace("auth");
        $this->mModperm= new Model_Modperm();
    }

    /**
     * 一般利用モジュールディレクトリ一覧を取得
     */
    public function moddirs()
    {
        $reg = Zend_Registry::getInstance();
        $sysconf = $reg["config"]->system;
        $ignore = array("default", "admin"); // 無視するディレクトリリスト
        if(isset($sysconf->default_module)){ // デフォルトモジュールディレクトリは無視
            $ignore[] = $sysconf->default_module;
        }
        if(isset($sysconf->admin_top_module)){ // 管理モジュールディレクトリは無視
            $ignore[] = $sysconf->admin_top_module;
        }
        $res = array();
        foreach($reg["moddirs"] as $path){
            $dir = basename($path);
            if(!in_array($dir, $ignore)){
                $res[] = $dir;
            }
        }
        return $res;
    }

    /**
     * dirname のメンバーかどうかを判定
     * - メンバーであればそのユーザの持つ一番高い権限値を返す
     */
    public function memberOf($dirname, $uid = null)
    {
        if($uid === null){
            $sAuth = new Zend_Session_Namespace("auth");
            $uid = $sAuth->user_id;
            if(!$uid){
                return false;
            }
        }
        if($dirname instanceof Ao_Controller_Action){
            // 第一引数がコントローラオブジェクトのときはモジュール名を取り出す
            $controller =& $dirname;
            $dirname = $controller->getRequest()->getModuleName();
        }
        $select = $this->mModperm->select()
            ->where("delete_flag = 0 or delete_flag is null")
            ->where("uid = ?", $uid)
            ->where("dirname = ?", trim($dirname))
            ->order("permission desc") // 権限の高い順にソート
            ;
        $vos = $this->mModperm->fetchAll($select);
        if($vos){
            $vo = current($vos);
            return $vo->get("permission");
        } else {
            return false;
        }
    }
    /**
     * 指定したモジュールのパーミッション定義情報を取得する
     * - 指定がない場合は null
     */
    public function permConfigOf($dirname)
    {
        $modconf = $this->registry["modconf"]->$dirname;
        $conf = array();
        if(isset($modconf->module->modperm) && $modconf->module->modperm){
            if(isset($modconf->modperm)){ // 定義がある
                foreach($modconf->modperm->toArray() as $k => $v){
                    $conf[$v] = $k;
                }
            }
            else { // 利用はするが定義がない
                $conf = array(0 => "guest", 1 => "member");
            }
        }
        else {
            $conf = null;    
        }
        return $conf;
    }

    /**
     * 各モジュールパーミッション設定情報とデフォルト値を配列で取得する
     */
    public function permConfigs($uid = null)
    {
        if($uid === null){
            $sAuth = new Zend_Session_Namespace("auth");
            $uid = $sAuth->user_id;
        }
        $modperm_conf = array();
        $moddirs = $this->moddirs();
        foreach($moddirs as $d){
            $modperm_conf[$d] = array(
                // 当該モジュールの権限設定
                "conf_array" => $this->permConfigOf($d),
                // 当該ユーザが $d のどの権限をもつか
                "default" => $this->memberOf($d, $uid),
            );
        }
        return $modperm_conf;
    }

    /**
     * パーミッション設定用フォームを配列で取得する。
     * キーはモジュールディレクトリ名。
     */
    public function permSelectForms($uid = null)
    {
        if($uid === null){
            $req = $this->controller->getRequest();
            $uid = $req->getParam("uid");
            if(!$uid){
                $sAuth = new Zend_Session_Namespace("auth");
                $uid = $sAuth->user_id;
            }
        }
        $confs = $this->permConfigs($uid);
        $forms = array();
        $v = new Ao_View();
        foreach($confs as $d => $c){
            if($c["conf_array"]){
                $default = $c["default"] ? $c["default"] : 0;
                $array = $c["conf_array"];
                $attr = array();
                $forms[$d] = $v->formSelect("modperm[$d]", $default, $attr, $array);
            } else {
                $forms[$d] = "site member";
            }
        }
        return $forms;
    }

    /**
     * uid のユーザの $moddir の権限値を取得。
     * 設定が無い場合は null。
     */
    public function myPermFor($moddir, $uid = null)
    {
        if($uid === null){
            $sAuth = new Zend_Session_Namespace("auth");
            $uid = $sAuth->user_id;
        }
        $model =& $this->mModperm;
        $select = $model->select();
        $select->where("uid = ?", $uid);
        $select->where("dirname = ?", $moddir);
        $select->where("delete_flag = 0 or delete_flag is null");
        $select->order("permission desc");
        $vos = $model->fetchAll($select);
        if($vos){
            return current($vos);
        } else {
            return null;
        }
    }

    public function getModpermVo($param = array())
    {
        $model =& $this->mModperm;
        $vo = $model->getVo($param);
        //$vo->set("is_released", 1);
        return $vo;
    }
    public function getModperm($id = null)
    {
        $model =& $this->mModperm;
        $select = $model->select();
        $select->where("delete_flag = 0 or delete_flag is null");
        $select->order("id asc");
        if($id){
            $select->where("id = ?", $id);
        }
        return $model->fetchAll($select);
    }
    public function getPage($limit = 20, $offset = 0, $orders = array("id asc"), $keyword = null)
    {
        $model =& $this->mModperm;
        $select = $model->select();
        $select->where("delete_flag = 0 or delete_flag is null");
        // ソート条件の正規化(?)
        if($orders == null){
            $orders = array("id asc");
        } else if (is_string($orders)) {
            $orders = array($orders);
        }
        // ソート条件登録
        foreach($orders as $order){
            $select->order($order);
        }
        if($keyword){
            //$keyword == Ao_Util_TextSanitizer::sanitize($keyword);
            $m   = $this->controller->getRequest()->getModuleName();
            $reg = Zend_Registry::getInstance();
            if(isset($reg["modconf"]->$m)
                && isset($reg["modconf"]->$m->search)
                && isset($reg["modconf"]->$m->search->target_columns)
            ){
                $target_columns = $reg["modconf"]->$m->search->target_columns;
                $target_columns = explode(",", $target_columns);
                foreach($target_columns as $col){
                    $cond[] = $col." like '%".$keyword."%'";
                }   
                $select->where(implode(" or ", $cond));
            }
        }
        $select->limit($limit, $offset);
        return $model->fetchAll($select);
    }
    public function getCount($keyword = null)
    {
        $count = 0;
        $model =& $this->mModperm;
        $select = $model->select()
            ->setIntegrityCheck(false)
            ->from($this->mModperm->name(), 'count(*) as count');
        $select->where("delete_flag = 0 or delete_flag is null");
        if($keyword){
            //$keyword == Ao_Util_TextSanitizer::sanitize($keyword);
            $m   = $this->controller->getRequest()->getModuleName();
            $reg = Zend_Registry::getInstance();
            if(isset($reg["modconf"]->$m)
                && isset($reg["modconf"]->$m->search)
                && isset($reg["modconf"]->$m->search->target_columns)
            ){
                $target_columns = $reg["modconf"]->$m->search->target_columns;
                $target_columns = explode(",", $target_columns);
                foreach($target_columns as $col){
                    $cond[] = $col." like '%".$keyword."%'";
                }   
                $select->where(implode(" or ", $cond));
            }
        }
        $res = $model->fetchAll($select,1);
        if($res){
            $count = $res[0]["count"];
        }
        return $count;
    }
    public function saveModperm($vo)
    {
        $model =& $this->mModperm;
        $model->getAdapter()->beginTransaction();
        try{
            $id = $model->save($vo);
            $model->getAdapter()->commit(); 
        } catch(Exception $e){
            $model->getAdapter()->rollback(); 
            print $e->getMessage();
            throw $e;
        }
        return $id;
    }
    public function deleteModperm($id = null)
    {
        $req = $this->controller->getRequest();
        if($id === null){
            $id = $req->getParam("id");
        }
        if($id == ""){
            throw new Zend_Exception("ID が取得できませんでした");
        }
        return $this->mModperm->del($id);
    }

/* pub_date が int 型であるときに利用するコード

    // 日付指定のデータを元に日付をUNIX_TIMESTAMP表現に変換する(開始)
    public function convYmdToIntPubDate(&$param)
    {
        $y = (int)$param["pub_date_y"];
        $m = (int)$param["pub_date_m"];
        $d = (int)$param["pub_date_d"];
        $h = (int)$param["pub_date_h"];
        $i = (int)$param["pub_date_i"];
        $s = 0; //(int)$param["pub_date_s"];
        // 日付が正しく指定されていなければ、時刻が選択されていても 0 とする
        if($y == "--" || $m == "--" || $d == "--"){
            $param["pub_date"] = 0;
        } else {
            $param["pub_date"] = mktime($h, $i, $s, $m, $d, $y);
        }
    }
    // UNIX_TIMESTAMP を日付データに変換する
    public function convIntToYmdPubDate(&$vo)
    {
        $utime = $vo->get("pub_date");
        if($utime == 0){
            $vo->set("pub_date_y", "--");
            $vo->set("pub_date_m", "--");
            $vo->set("pub_date_d", "--");
            $vo->set("pub_date_h", "--");
            $vo->set("pub_date_i", "--");
        } else {
            $vo->set("pub_date_y", date("Y", $vo->get("pub_date")));
            $vo->set("pub_date_m", date("m", $vo->get("pub_date")));
            $vo->set("pub_date_d", date("d", $vo->get("pub_date")));
            $vo->set("pub_date_h", date("H", $vo->get("pub_date")));
            $vo->set("pub_date_i", date("i", $vo->get("pub_date")));
        }
    }
*/
/* sample of left join
    public function getRolesByUid($rid = null)
    {
        $model = new Model_Role();
        $model_ur= new Model_RoleRole();
        $select = $model->select();
        $select->setIntegrityCheck(false)
            ->from(array("r" => $model->name()), array("*"))
            ->joinLeft(
                array("ur" => $model_ur->name()),
                "r.rid = ur.rid",
                array("*"))
            ->where("ur.rid = ?", (int)$rid)
            ->order("r.rid asc")
            ;
        $res = $model->fetchAll($select, true);
        if($res){
            return $this->_assignCommonVos($res);
        } else {
            return null;
        }
    }
*/

}
