<?php
/**
 * Admin User Base Controller
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
 * @package       Ao.modules.admin.Controller.Base
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class Admin_UserBaseController extends Ao_Controller_AclAction
{
    protected $_logger;

    public function init()
    {
        parent::init();
        $registry = Zend_Registry::getInstance();
        $this->_logger = $registry["logger"];
        $this->_config = $registry["config"];
        $this->_authSess = new Zend_Session_Namespace("auth");
    }
    public function indexAction()
    {
/*
        $system = array(
            "apache" => apache_get_version(),
            "mysql" => mysql_get_server_info(),
            "php" => PHP_VERSION,
            "zend" => Zend_Version::VERSION,
            "aflib" => Ao_Version::VERSION,
        );
*/
        $this->view->system = $system;

        //$manager = new Manager_User($this);
        //$users = $manager->gets();
        //$this->view->users = $users;

        // ページャを使う
        $req = $this->getRequest();
        $m = $req->getModuleName();
        $c = $req->getControllerName();
        $kw = $req->getParam("kw");
        $manager = new Manager_User($this);
        $count = $manager->getCount($kw);
        $this->view->count = $count;
        $reg = Zend_Registry::getInstance();
        $conf = $reg["config"];
        $offset = (int)$req->getParam("offset");
        $limit = $req->getParam("limit");
        if($limit == ""){
            $limit = $conf->search->limit;
        }
        if($kw){
            $base_url = $conf->site->root_url."/$m/$c/?kw=".urlencode($kw)."&amp;limit=$limit&amp;offset=";
        } else {
            $base_url = $conf->site->root_url."/$m/$c/?limit=$limit&amp;offset=";
        }
        $nav = new Ao_Util_Pagenav();
        $nav->set("total", $count);
        $nav->set("perpage", $limit);
        $nav->set("current", $offset);
        $nav->set("url", $base_url);
        $this->view->pagenav = $nav->renderNav();
        $this->view->users = $manager->getPage($limit, $offset, null, $kw);
        $this->view->kw = $kw;
    }
    public function newAction()
    {
        $req = $this->getRequest();
        $form = new Form_User_New($this);
        $this->view->form = $form->formElements();
        $this->view->action_name 
            = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/new-regist/";
    }
    public function newConfirmAction()
    {
    }
    public function newRegistAction()
    {
        $req = $this->getRequest();
        $form = new Form_User_New($this);
        $this->view->form = $form->formElements();
        if(!$req->isPost()){
            throw new Zend_Exception("不正なアクセスです");
        }

        // 送信データ取得
        $_params = $req->getParams();
        if($this->_config->system->email_login){
            $_params["login"] = $_params["email"];
            //    $vo->set("login", $vo->get("email"));
        }

        // foreard用変数
        $m = $req->getModuleName();
        $c = $req->getControllerName();

        $form->validate($_params);
        if($form->errorNum()){
            // エラーがあったらメッセージをアサインしてフォームに戻す
            $this->view->error = $form->errors();
            return $this->_forward("new", $c, $m);
        }

        // ボタンに応じたアクション
        if($req->isPost() && $req->getPost("submit") == "戻る"){
            return $this->_forward("new", $c, $m);
        }
        else if(
            $req->isPost() && $req->getPost("submit") == "登録"
        ){

            $manager = new Manager_User($this);
            $vo = $manager->getVo($_params);
            $pw = $vo->get("passwd"); // 生パスワード
            $vo->set("passwd", $manager->getEncPassword($pw)); // 暗号化
            $manager->save($vo);
            //$this->view->message = "登録が完了しました";
            //$this->getFrontController()->setParam("noViewRenderer", true);
            //$c = $req->getControllerName(); 
            //$a = $req->getActionName();
            //echo $this->view->render($c."/".$a."_complete.html");
            //exit;
        }
        else {
            $req->setParam("sys_message", "不正なアクションです");
            return $this->_forward("new", $c, $m);
        }
    }

    public function editAction()
    {
        $req = $this->getRequest();
        $uid = $req->getParam("uid");
        if(!$uid){
            throw new Zend_Exception("ユーザIDが取得できませんでした");
        }

        // DB データを取得する
        $manager = new Manager_User($this);
        $_data = $manager->getUser($uid);
        $data = current($_data);

        // REQUEST データと DB データをマージする
        $_params = $req->getParams();
        $params = array_merge($data, $_params);

        $reg = Zend_Registry::getInstance();
        $conf = $reg["config"];


        // フォーム要素を準備する
        $form = new Form_User_New($this);
        $form->setParams($params);
        $this->view->form = $form->formElements();
        $this->view->params = $params;
        $this->view->action_name 
            = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/edit-regist/";

        //---- Role 情報
        $myroles = $manager->getRolesByUid($uid);
        $myrole_ids = array();
        foreach($myroles as $myrole){
            $myrole_ids[] = $myrole->get("rid");
        }
        $managerR = new Manager_Role($this);
        $roles = $managerR->gets();
        foreach($roles as $i => $r){
            if(in_array($r->get("rid"), $myrole_ids)){
                $r->set("belong", true);
            } else {
                $r->set("belong", false);
            }
            $roles[$i] = $r;
        }
        $this->view->roles = $roles;
        $this->view->role_action_name 
            = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/role-regist/";
        //---- /Role 情報

        //---- Modperm 情報
        $mp_manager = new Manager_Modperm($this);
        $this->view->modperm_form = $mp_manager->permSelectForms();
        $this->view->modperm_action = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/modperm-regist/";
        //---- /Modperm 情報
    }
    public function editConfirmAction()
    {
    }
    public function editRegistAction()
    {
        $req = $this->getRequest();
        $form = new Form_User_Edit($this);
        $this->view->form = $form->formElements();
        if(!$req->isPost()){
            throw new Zend_Exception("不正なアクセスです");
        }

        
        // 送信データ取得
        $_params = $req->getParams();

        // foreard用変数
        $m = $req->getModuleName();
        $c = $req->getControllerName();

        $form->validate($_params);
        if($form->errorNum()){
            // エラーがあったらメッセージをアサインしてフォームに戻す
            $this->view->error = $form->errors();
            return $this->_forward("edit", $c, $m);
        }

        // ボタンに応じたアクション
        if($req->isPost() && $req->getPost("submit") == "戻る"){
            return $this->_forward("edit", $c, $m);
        }
        else if(
            $req->isPost() && $req->getPost("submit") == "登録"
        ){

            $manager = new Manager_User($this);
            $vo = $manager->getVo($_params);
            // パスワードが入力されていたら更新する
            if($vo->get("passwd")){
                $pw = $vo->get("passwd");
                $vo->set("passwd", $manager->getEncPassword($pw));
            } else {
                // 変更無しの場合現在のパスワードをそのまま保持
                $_data = $manager->getUser($vo->get("uid"));
                $data = current($_data);
                $vo->set("passwd", $data["passwd"]);
            }
            // Email ログイン時は login に email を入れる
            if($this->_config->system->email_login){
                $vo->set("login", $vo->get("email"));
            }

            $manager->save($vo);
            //$this->view->message = "登録が完了しました";
            //$this->getFrontController()->setParam("noViewRenderer", true);
            //$c = $req->getControllerName(); 
            //$a = $req->getActionName();
            //echo $this->view->render($c."/".$a."_complete.html");
            //exit;
        }
        else {
            $req->setParam("sys_message", "不正なアクションです");
            return $this->_forward("edit", $c, $m);
        }
    }

    public function deleteAction()
    {
        $req = $this->getRequest();
        $uid = $req->getParam("uid");
        if(!$uid){
            throw new Zend_Exception("ユーザIDが取得できませんでした");
        }

        // DB データを取得する
        $manager = new Manager_User($this);
        $data = $manager->getUser($uid);

        $this->view->params = $data->toArray();
        $this->view->action_name 
            = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/delete-do/";

        //---- Role 情報
        $myroles = $manager->getRolesByUid($uid);
        $myrole_ids = array();
        if($myroles){
            foreach($myroles as $myrole){
                $myrole_ids[] = $myrole->get("rid");
            }
        }
        $managerR = new Manager_Role($this);
        $_roles = $managerR->gets();
        $roles = array();
        foreach($_roles as $i => $r){
            if(in_array($r->get("rid"), $myrole_ids)){
                $r->set("belong", true);
                $roles[] = $r;
            }
        }
        $this->view->roles = $roles;
        //---- /Role 情報
    }
    public function deleteDoAction()
    {
        $req = $this->getRequest();
        // 送信データを取得する
        $uid = $req->getParam("uid");
        $rids = $req->getPost("rid");
        if(!$uid){
            throw new Zend_Exception("ユーザIDが取得できませんでした");
        }

        // DB データを取得する
        $manager = new Manager_User($this);
        $data = $manager->getUser($uid);

        if(!$data){
            throw new Zend_Exception("ユーザ情報が取得できませんでした");
        }

        // ロール情報を削除(UserRole::del)
/* 論理削除にともない、ロール情報を残す
        if($rids){
            $mUserRole = new Model_UserRole();
            foreach($rids as $rid){
                $mUserRole->del($rid);
            }
        }
*/

        // ユーザ情報を削除
        //$manager->delete($uid);
        $manager->deleteByUpdate($uid);

        $this->view->params = $data->toArray();
    }

    public function roleRegistAction()
    {
        $req = $this->getRequest();
        $params = $req->getParams();
        if(!$req->isPost() || $req->getPost("submit") != "所属ロールを更新"){
            $this->view->message = "不正なアクセスを検出しました";
            $this->getFrontController()->setParam("noViewRenderer", true);
            $c = $req->getControllerName(); 
            $a = $req->getActionName();
            echo $this->view->render($c."/".$a."_error.html");
            exit;
        }

        $uid = $req->getPost("uid");

        $mUser = new Manager_User($this);
        $mRole = new Manager_Role($this);
        $mUserRole = new Manager_UserRole($this);

        // 送信された rid
        $_post_rids= $params["rid"];
        $post_rids = array();
        foreach($_post_rids as $rid => $flag){
            if($flag){
                $post_rids[] = $rid;
            }
        }

        // DB 登録されている rid
        $myroles = $mUser->getRolesByUid($uid);
        $myrole_ids = array();
        if($myroles){
            foreach($myroles as $myrole){
                $myrole_ids[] = $myrole->get("rid");
            }
        }

        // 送信された rid に含まれていない DB の rid = 削除対象
        $del_rids = array();
        foreach($myrole_ids as $db_rid){
            if(!in_array($db_rid, $post_rids)){
                $del_rids[] = $db_rid;
            }
        }
//print_r($del_rids);

        // 送信された rid にあって DB に含まれていない rid = 追加対象
        $add_rids = array();
        foreach($post_rids as $post_id){
            if(!in_array($post_id, $myrole_ids)){
                $add_rids[] = $post_id;
            }
        }
//print_r($add_rids);exit;

        if($del_rids){
            $mUserRole->remove($uid, $del_rids);
        }
        if($add_rids){
            $mUserRole->add($uid, $add_rids);
        }

        $this->view->uid = $uid;
    }
    public function modpermRegistAction()
    {
        $req = $this->getRequest();
        $params = $req->getParams();
        if(!$req->isPost() || $req->getPost("modperm_submit") != "権限設定を保存"){
            $this->view->message = "不正なアクセスを検出しました";
            $this->getFrontController()->setParam("noViewRenderer", true);
            $c = $req->getControllerName(); 
            $a = $req->getActionName();
            echo $this->view->render($c."/".$a."_error.html");
            exit;
        }

        $uid = $req->getPost("uid");
        $mp_manager = new Manager_Modperm($this);

        $modperms = $req->getPost("modperm");

        foreach($modperms as $moddir => $permission){
            $vo = $mp_manager->myPermFor($moddir, $uid);
            if($vo){
                $vo->set("permission", $permission);
            } else {
                $conf = array(
                    "uid" => $uid,
                    "dirname" => $moddir,
                    "permission" => $permission
                );
                $vo = $mp_manager->getModpermVo($conf);
            }
            $mp_manager->saveModperm($vo);
        }

        $registry = Zend_Registry::getInstance();
        $url = $registry["config"]->site->root_url;
        $mod = $this->getRequest()->getModuleName();
        $ctr = $this->getRequest()->getControllerName();
        $this->_redirect("$url/$mod/$ctr/edit/?uid=$uid");
    }
}
