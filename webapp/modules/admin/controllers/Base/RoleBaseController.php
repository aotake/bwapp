<?php
/**
 * Admin Role Base Controller
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
class Admin_RoleBaseController extends Ao_Controller_AclAction
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
        $system = array(
            "apache" => apache_get_version(),
            "mysql" => mysql_get_server_info(),
            "php" => PHP_VERSION,
            "zend" => Zend_Version::VERSION,
            "aflib" => Ao_Version::VERSION,
        );
        $this->view->system = $system;

        $manager = new Manager_Role($this);
        $roles = $manager->gets();
        $this->view->roles = $roles;
    }
    public function newAction()
    {
        $req = $this->getRequest();
        $form = new Form_Role_New($this);
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
        $form = new Form_Role_New($this);
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
            return $this->_forward("new", $c, $m);
        }

        // ボタンに応じたアクション
        if($req->isPost() && $req->getPost("submit") == "戻る"){
            return $this->_forward("new", $c, $m);
        }
        else if(
            $req->isPost() && $req->getPost("submit") == "登録"
        ){

            $manager = new Manager_Role($this);
            $vo = $manager->getVo($_params);
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
        $rid = $req->getParam("rid");
        if(!$rid){
            throw new Zend_Exception("ユーザIDが取得できませんでした");
        }

        // DB データを取得する
        $manager = new Manager_Role($this);
        $_data = $manager->getRole($rid);
        $data = current($_data);

        // REQUEST データと DB データをマージする
        $_params = $req->getParams();
        $params = array_merge($data, $_params);

        $reg = Zend_Registry::getInstance();
        $conf = $reg["config"];

        // フォーム要素を準備する
        $form = new Form_Role_Edit($this);
        $form->setParams($params);
        $this->view->form = $form->formElements();
        $this->view->action_name 
            = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/edit-regist/";
    }
    public function editConfirmAction()
    {
    }
    public function editRegistAction()
    {
        $req = $this->getRequest();
        $form = new Form_Role_Edit($this);
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

            $manager = new Manager_Role($this);
            $vo = $manager->getVo($_params);
            $manager->update($vo);
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
        $rid = $req->getParam("rid");
        if(!$rid){
            throw new Zend_Exception("ユーザIDが取得できませんでした");
        }

        // DB データを取得する
        $manager = new Manager_Role($this);
        $_data = $manager->getRole($rid);
        $data = current($_data);

        $this->view->params = $data;
        $this->view->rid = $rid;
        $this->view->action_name 
            = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/delete-do/";
    }
    public function deleteDoAction()
    {
        $req = $this->getRequest();
        if(!$req->isPost()){
            throw new Zend_Exception("不正なアクセスです");
        }

        
        // 送信データ取得
        $params = $req->getParams();
        $rid = $params["rid"];
        if($rid == "" || !preg_match("/^[1-9][0-9]*$/",$rid) ){
            throw new Zend_Exception("IDが取得できませんでした($rid)");
        }

        // foreard用変数
        $m = $req->getModuleName();
        $c = $req->getControllerName();

        // ボタンに応じたアクション
        if($req->isPost() && $req->getPost("submit") == "戻る"){
            return $this->_forward("delete", $c, $m);
        }
        else if(
            $req->isPost() && $req->getPost("submit") == "削除"
        ){

            $manager = new Manager_Role($this);
            $manager->delete($rid);
            //$this->view->message = "登録が完了しました";
            //$this->getFrontController()->setParam("noViewRenderer", true);
            //$c = $req->getControllerName(); 
            //$a = $req->getActionName();
            //echo $this->view->render($c."/".$a."_complete.html");
            //exit;
        }
        else {
            $req->setParam("sys_message", "不正なアクションです");
            return $this->_forward("delete", $c, $m);
        }
    }


    public function belongUserAction()
    {
        $req = $this->getRequest();
        $rid = $req->getParam("rid");
        if(!$rid){
            throw new Zend_Exception("ID が取得できませんでした");
        }
        $manager = new Manager_UserRole($this);
        $users = $manager->getBelongUsers($rid);
        $not_users = $manager->getUsersExcept($users);

        $managerR = new Manager_Role($this);
        $this->view->role = $managerR->getRole($rid);
        $this->view->users = $users;
        $this->view->not_users = $not_users;
    }
}
