<?php
/**
 * Module Action Controller
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
 * @package       Ao.Controller
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
//
// モジュールコントローラ用基底クラス
//      モジュール管理用は Ao_Controller_ModAdmAction を使う
//
class Ao_Controller_ModAclAction extends Ao_Controller_AclAction
{
    protected $_not_installed;// array of no-installed module name
    protected $_installed;    // array of installed module name
    protected $is_installed;  // flag

    var $modname;
    var $modpath;

    public function init(){
        parent::init();
        $front = Zend_Controller_Front::getInstance();
        $this->modpath = $front->getModuleDirectory();
        // ---
        // モジュール名が xxx でも、 ディレクトリ名が xxx ではない場合を想定
        // 具体的には xxx = default のときに対応（通常は一致しているはず）
        // ---
        // ZF 上モジュール名
        $_zf_modname = $this->getRequest()->getModuleName();
        // 実際のモジュールディレクトリ名
        $this->modname = basename(dirname($front->getControllerDirectory($_zf_modname)));
        $this->is_installed = false;

        // 
        // 「Admin コントローラ以外のとき」はインストールチェックをする
        //
        //      -> AdminController::installAction() を実行しようとして
        //         preDispatch() が再度インストールチェックしないように
        // 
        // この処理は preDispatch() におくと _forward の度い実行されるので
        // init() 内に置くこと
        // 
        $check = $this->installCheck();
        if($check == false
            && $this->getRequest()->getControllerName() != "admin"
        ){
            $mod = $this->getRequest()->getModuleName();
            $ctr = $this->getRequest()->getControllerName();
            $p = array(
                "error" => "テーブルがインストールされていません",
                "controller" => $ctr,
                "module" => $mod,
            );
            //$this->_forward("install", "admin", $mod, $p);
            $this->_forward("install", $ctr, $mod, $p);
        }
        else if($check == false
            && $this->getRequest()->getControllerName() == "admin"
        ){
            // IndexController は ModAclAction を継承していることが前提
            $mod = $this->getRequest()->getModuleName();
            return $this->_redirect("/$mod/index/");
        }

    }
    public function preDispatch()
    {
        parent::preDispatch();
/*
        // 「Admin コントローラ以外のとき」はインストールチェックをする
        //
        //      -> AdminController::installAction() を実行しようとして
        //         preDispatch() が再度インストールチェックしないように
        $check == $this->installCheck();
        if($check == false
            && $this->getRequest()->getControllerName() != "admin"
        ){
            $mod = $this->getRequest()->getModuleName();
            $ctr = $this->getRequest()->getControllerName();
            $p = array("error" => "テーブルがインストールされていません");
            //$this->_forward("install", "admin", $mod, $p);
            $this->_forward("install", $ctr, $mod, $p);
        }
*/
        // ブロック用ロジックを実行して view にアサインする
        $this->doModuleBlockLogic();
    }
    public function doModuleBlockLogic()
    {
        // 自身のロール情報（最高権限）を取得
        /* TODO: ブロックアクセス権限管理
        if($this->_userInfo){
            $myrole = $this->_userInfo->get("role");
            $myToplevelRole = current($myrole);
            $role = $myToplevelRole->get("name");
        } else {
            $role = "guest";
        }
        */
        $default_mod = $this->getRequest()->getModuleName();
        $modconf = $this->_registry["modconf"];
        $block_cache = array();
        foreach($modconf as $modname => $conf){
            if($conf->block){
                $bconfs = $conf->block->toArray();
                foreach($bconfs as $bname => $bconf){
                    try{
                        $obj = new $bconf["class"]($this);
                        if(method_exists($obj, $bconf["method"])){
                            $block_cache[$modname][$bname] = $obj->$bconf["method"]();
                        }
                        else{
                            $this->_registry["logger"]->warn("skip: Not found, ".$modname." module,  ".$bconf["class"]."::".$bconf["method"]."()");
                        }
                    } catch(Exception $e){
                        $this->_registry["logger"]->err("skip: block err, ".$modname." module,  ".$bconf["class"]."::".$bconf["method"]."(), msg=".$e->getMessage());
                    }
                }
            }
        }
        $this->_registry["blockCache"] = $block_cache;
        $this->view->blockCache = $block_cache;
    }

    // インストール不要：true;
    // 要インストール: false;
    public function installCheck($modpath = null)
    {
        $modname = $this->modname;
        if($modpath == null){
            $modpath = $this->modpath;
        }
        $dbtype = $this->_registry["config"]->db->type;
        $sqlfile = $modpath."/sql/".$dbtype.".sql";
        $metafile = $modpath."/sql/meta.xml";

        // DB を使わない場合は常に true
        if(isset($this->_registry["config"]->system->use_db)){
            $use_db = (int)$this->_registry["config"]->system->use_db;
        } else {
            $use_db = 1;
        }
        if($use_db == 0){
            $this->is_installed = true;
            return true;
        }

        // ---- 以下は DB 利用時の処理 ----

        // meta.xml があって sql ファイルが無い場合は生成する
        if(file_exists($metafile) && !file_exists($sqlfile)) {
            $processor = $this->_registry["webappDir"]."/tool/metaprocessor.php";
            $cmd_response = shell_exec("php $processor $metafile");
//            print $cmd_response;
        }

        // どういうわけだか SQL ファイルが無い場合, DB を使わないモジュールとして true とする
        if(!file_exists($sqlfile)){
            $this->is_installed = true;
            return true;
        }

        $prefix = $this->_registry["config"]->db->prefix;
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        // DB 上の全てのテーブルリストを取得
// TODO:このへんにキャッシュをもたせたい
        if($this->_registry["config"]->db->type == "pgsql"){
            $res = $db->query("select relname as TABLE_NAME from pg_stat_user_tables");
        } else {
            $res = $db->query("show tables");
        }
        $installed = array();
        foreach($res->fetchAll() as $k => $t){
            list($key, $table) = each($t);
            $installed[] = $table;
        }

        // モジュール SQL ファイルのテーブルが作成されているか確認
        $sqlfile_line = file($sqlfile);
        $not_installed = array();
        foreach($sqlfile_line as $line){
            if(!preg_match("/^create table (.+)\s*\(\s*$/i", $line, $m)){
                continue;
            }
            $_table = trim($m[1]);
            $_table = str_replace("<PREFIX>_", "", $_table);
            $_table = str_replace("<MODULE>_", "", $_table);

            $check_tbl = $prefix."_".$modname."_".$_table;

            if(!in_array($check_tbl, $installed)){
                $not_installed[] = $check_tbl;
            }
        }

        // 当該モジュールのインストール済みテーブルを検出
        $pat = $prefix."_".$modname."_";
        $this->_installed = array();
        foreach($installed as $tbl){
            if(preg_match("/^$pat/i", $tbl)){
                $this->_installed[] = $tbl;
            }
        }

        // 未作成のモジュールテーブルがあれば false
        if(count($not_installed)){
            $this->_not_installed = $not_installed;
            $this->is_installed = false;
            return false;
        }
        $this->_installed = $installed;
        $this->is_installed = true;
        return true;
    }

    /*************************************************************************
     * ModAdmAction だったやつ 
    *************************************************************************/
    public function installAction()
    {
        $userInfo = $this->getUserInfo();
        if(!$userInfo){
            throw new Zend_Exception("guest user couldn't install.");
        }
        $userRoles = $userInfo->get("role");
        $role = $userRoles[0];
        if($role->get("name") != "admin"){
            throw new Zend_Exception("no admin user");
        }

        $req = $this->getRequest();

        //$installed = parent::installCheck();
        $this->view->installed = $this->is_installed;

        // テンプレートディレクトリチェック用
        $front = Zend_Controller_Front::getInstance();
        $default_ctr = $front->getDefaultModule();
        $default_path = $front->getModuleDirectory($default_ctr);
        $inst_tpl_path = $this->_registry["webappDir"]
            ."/modules/default/templates";
        // テンプレートディレクトリをデフォルトモジュールに変更
        // デフォルトモジュールになければ default のテンプレートを使う
        if(file_exists($default_path."/templates/index/install.html")){
            $tpl = $default_path."/templates";
        }
        else if(file_exists($inst_tpl_path."/index/install.html")){
            $tpl = $inst_tpl_path;
        }
        else {
            throw new Zend_Exception("no installer template");
        }
        $this->view->setScriptPath($tpl);


        // インストール処理
        if(!$this->is_installed && $req->isPost()){
            $c = $this->getRequest()->getControllerName();
            if($this->installTable()){
                $this->view->msg = "成功しました";
            } else {
                $this->view->msg = "失敗しました";
            }

            echo $this->view->render("index/install-complete.html");
//            echo $this->view->render("$c/install-complete.html");
            exit;
        }

        // インストール画面表示
        $error = $req->getParam("error");
        $this->view->error = $error;
        $this->view->action_name = $this->_registry["config"]->site->root_url
            ."/".$this->getRequest()->getModuleName()
            ."/".$this->getRequest()->getControllerName()
            ."/".$this->getRequest()->getActionName()
            ."/";
        $this->view->install_tables = $this->_not_installed;

        echo $this->view->render("index/install.html");
        exit;
    }
    private function installTable()
    {   
        $modpath = $this->modpath;
        $modname = $this->modname;
        $prefix = $this->_registry["config"]->db->prefix;
        $dbtype = $this->_registry["config"]->db->type;
        $sqlfile = $modpath."/sql/".$dbtype.".sql";
        $skel = file_get_contents($sqlfile);
            
        $db = $this->_registry["config"]->db;
        $dsn = $db->dsn;
        $skel = preg_replace("/<DBNAME>/", $dsn->dbname, $skel);
        $skel = preg_replace("/<DBUSER>/", $dsn->username, $skel);
        $skel = preg_replace("/<DBPASS>/", $dsn->password, $skel);
        $skel = preg_replace("/<PREFIX>/", $db->prefix, $skel);
        $skel = preg_replace("/<CHARSET>/", $db->charset, $skel);
        $skel = preg_replace("/<MODULE>/", $modname, $skel);

        $sql_list = array();
        // DBtype が pgsql のとき
        if($dbtype == "pgsql"){
            $sqlutil = new Ao_Util_SqlUtility();
            $sqlutil->splitPgSqlFile($sql_list, $skel);
        } 
        // DBtype が pgsql 以外は mysql として処理
        else {
            Ao_Util_SqlUtility::splitMySqlFile($sql_list, $skel);
        }

        $db = $this->_registry["zdb_adapter"];
        $db->beginTransaction();
        foreach($sql_list as $sql){
            if(preg_match("/^create table ([^ \(]+)/i", $sql, $m)){
                if(!in_array($m[1], $this->_not_installed)){
                    continue;
                }
            }
            // CREATE TABLE 以外のクエリは、最初のモジュール install 時以外
            // スキップする（INSERT や DROP は初めてのインストール時のみ有効
            else if(count($this->_installed) > 0){
                continue;
            }

            try{
                $db->query($sql);
            } catch(Exception $e){
                $this->view->error = $e->getMessage();
                $db->rollBack();
                $this->view->error .= "<br />rollback done.";
                // TODO: もしテーブル削除漏れがあれば削除
                // (rollback 効いてないとき）

                return false;
            }
        }
        $db->commit();
        return true;
    }
}
