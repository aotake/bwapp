<?php
/**
 * Admin Module Action Controller
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
class Ao_Controller_ModAdmAction extends Ao_Controller_ModAclAction
{
    var $modname;
    var $modpath;

    public function init(){
        parent::init();
        $this->modname = $this->getRequest()->getModuleName();
        $this->modpath = $this->_registry["webappDir"]
                            ."/modules/".$this->modname;
    }
    public function preDispatch()
    {
        parent::preDispatch();
    }

    //public function installCheck($modpath = null) {}

    public function installAction()
    {
        $req = $this->getRequest();
        $error = $req->getParam("error");
        $this->view->error = $error;

        $installed = parent::installCheck();
        $this->view->installed = $installed;

        if(!$installed && $req->isPost()){
            if($this->installTable()){
                $this->view->msg = "成功しました";
            } else {
                $this->view->msg = "失敗しました";
            }
            $this->getFrontController()->setParam("noViewRenderer", true);
            echo $this->view->render("admin/install-complete.html");
            return;
        }
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
            //if(preg_match("/^create table (.+)\s*\(\s*$/i", $sql, $m)){
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
