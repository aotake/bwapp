<?php
/**
 * Acl Action Controller
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
// 認証処理用基底クラス
//
class Ao_Controller_AclAction extends Ao_Controller_Action
{
    protected $_userInfo = null;

    public function init()
    {
        parent::init();
    }

    public function preDispatch()
    {
        parent::preDispatch();
        return $this->_checkAcl();
    }

    private function _checkAcl()
    {
        $registry = Zend_Registry::getInstance();
        if(isset($registry["isDbError"]) == false
            || $registry["isDbError"] == false
        ) {
            // セッションにユーザIDがあればユーザ情報を取得する
            $auth = new Zend_Session_Namespace("auth");
            if(!empty($auth->user_id)){
                $this->_userInfo = $this->_getUserInfo($auth->user_id);
            }
        }
        else {
            // DB エラー時は認証情報をクリアする
            Zend_Session::namespaceUnset("auth");
        }

        // view でログインユーザ情報を表示するために埋め込む
        $this->view->_userInfo = $this->_userInfo;

        // ----- アクセスコントロール設定
        $acl = new Zend_Acl();
        //$acl->add(new Zend_Acl_Resource("index"));

        $acl->addRole(new Zend_Acl_Role("guest"));
        $acl->addRole(new Zend_Acl_Role("member"), "guest");
        $acl->addRole(new Zend_Acl_Role("admin"), "member");

        /*
         * config.ini で定義いたコントロールデータを取り込む
         *
         * 書式
         * resouce1=action1:action2:action3,resouce2=action1:action2, ....
         */
        $allow_all_mod = array();
        $logger = $registry["logger"];

        $roles = array("guest", "member");

        foreach($roles as $_role){
            $_acl_conf = $registry["config"]->acl->toArray();
            if(array_key_exists($_role, $_acl_conf)){
                foreach($_acl_conf[$_role] as $module => $_c_seq){
                    if($_c_seq == '*'){
                        // 全てのコントローラにアクセス可能なモジュールを保存
                        $allow_all_mod[] = $module;
                        $resource = $module."All";
                        $priv = null;
                        $acl->add(new Zend_Acl_Resource($resource));
                        $acl->allow($_role, $resource);
                    }
                    else {
                        // 許可コントローラの指定が存在する場合
                        $ca_arr = explode(",", $_c_seq);
                        foreach($ca_arr as $ca_seq){
                            if(preg_match("/([a-z]+)=([a-z:]+)/", $ca_seq, $match)){
                                $controller = $match[1];
                                $actions = explode(":", $match[2]);
                            } else {
                                $controller = $ca_seq;
                                $actions = null;
                            }
                            $resource = $module.ucfirst($controller);
                            $priv = $actions;
                            $acl->add(new Zend_Acl_Resource($resource));
                            $acl->allow($_role, $resource, $priv);
                        }
                    }
                }
            }
        } // end: foreach: roles

        // 管理者は全てにアクセス可能
        $acl->allow("admin");


        // ----- アクセスコントロールチェック
        $req = $this->getRequest();
        $m = $req->getModuleName();
        $c = $req->getControllerName();
        $a = $req->getActionName();

        if(in_array($m, $allow_all_mod)){
            $r = $m."All";
        } else {
            $r = $m.ucfirst($c);
        }

        // TODO :check group
        if(empty($this->_userInfo)){
            $role = "guest";
        } else {
            $roleInfo = $this->_userInfo->get("role");
            if($roleInfo){
                $role = $roleInfo[0]->get("name");
                $this->view->user_name = $this->_userInfo->get("login");
            } else {
                $role = "guest";
            }
        }

        // グループ情報を view(layout) で使う
        $this->view->role = $role;

        $is_allowed = false;
        if($acl->has($r)){
            $is_allowed = $acl->isAllowed($role, $r, $a);

            // admin コントローラの時は
            // 許可されてるリソース(ALL)でも強制的に拒否する
            if($c == "admin" && $role != "admin"){
                $is_allowed = false;
            }
        } else {
            if($role == "admin"){
                $is_allowed = true;
            }
        }

        // アクセス許可状態にあわせて処理
        if(!$is_allowed){
            if(empty($auth->user_id)){
                $front = $this->getFrontController();
                $dispatcher = $front->getDispatcher();
                $dispatcher->setParam("originalRequest", clone $req);
                $module = "default";
                if(!empty($registry["config"]->system->logon_module)){
                    $module = $registry["config"]->system->logon_module;
                    if(isset($registry["config"]->system->default_module)){
                        $default_module = $registry["config"]
                                            ->system
                                            ->default_module;
                        if($default_module == $module){
                            $module = "default";
                        }
                    }
                }
                // default モジュールではこの if 文なしでもいけたが
                // ほかのモジュールの logon コントローラを使おうとしたら
                // 無限ループになってしまう。
                if($c != "logon" || ($c == "logon" && $a != "index")){
                    return $this->_forward("index", "logon", $module);
                }
            }
            else {
                $this->_redirect("/sorry");
            }
        }
    }

    protected function _getUserInfo($user_id)
    {
        $manager = new Manager_User();
        $user_model = new Model_User();
        try { 
            $rowset = $user_model->find($user_id);
            if (count($rowset)) {
                $user_vo = current($rowset);
                // ユーザデータがあったら Role 情報も付与してやる
                $user_vo->set("role", $manager->getRolesByUid($user_id));
                return current($rowset);
            }
        } catch(Zend_Exception $e){
            $r = Zend_Registry::getInstance();
            $r["logger"]->err("uid = $user_id, method=".__METHOD__.",file=".__FILE__.",line=".__LINE__.",".$e->getMessage());
            throw $e;
        }
        return false;
    }

    // 継承したコントローラでユーザデータを取得する
    public function getUserInfo()
    {
        $auth = new Zend_Session_Namespace("auth");
        return $this->_getUserInfo($auth->user_id);
    }
}
