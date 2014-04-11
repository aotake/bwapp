<?php
/**
 * Default Logon Base Controller
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
 * @package       Ao.modules.admin.Controller
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author     Takeshi Aoyama (aotake@bmath.org)
 */
class LogonBaseController extends Ao_Controller_AclAction
{
    const SESS_FORM_OPT = "form_opt";
    private $_sessFormOpt = null;

    public function init()
    {
        parent::init();
        $this->_sessFormOpt = new Zend_Session_Namespace(self::SESS_FORM_OPT);

        // Logon コントローラに来る前のアクセスした request オブジェクト
        $orig_req = $this->getInvokeArg("originalRequest");
        if($orig_req){
            $this->view->assign("original_path", $orig_req->getPathInfo());
        }
    }

    public function indexAction()
    {
        $sess_form_opt = new Zend_Session_Namespace("form_opt");
        $sess_auth = new Zend_Session_Namespace("auth");

        $request = $this->getRequest();

        $username = $request->getPost("username");
        if(empty($username)){
            // ログインユーザがアクセスしてきたら TOP へリダイレクト
            if(isset($sess_auth->user_id)){
                $this->_redirect("/");
                exit;
            }
            // まだログインしてなければログイン画面
            $this->_resetToken();
            $this->view->assign("refmod", $request->getParam("refmod"));
            return;
        }
        //$token = $request->getPost("token");
        //if($token != $sess_form_opt->token){
        //    $msg = "トークンのチェックに失敗しました";
        //    $this->view->assign("result_msg", $msg);
        //    $this->_resetToken();
        //    return;
        //}

        $username = trim($request->getPost("username"));
        $password = $request->getPost("password");

        $config = Zend_Registry::get("config");
        if($config->system->email_login){
            $login_name = "email";
            $v = new Zend_Validate_Email();
            if(!$v->isValid($username)) {
                $msg = "メールアドレスを入力して下さい";
                $this->view->assign("result_msg", $msg);
                $this->_resetToken();
                return;
            }
        } else {
            $login_name = "login";
            $v = new Zend_Validate_Alnum();
            if(!$v->isValid($username)) {
                $msg = "ユーザ名を半角英数時で入力して下さい";
                $this->view->assign("result_msg", $msg);
                $this->_resetToken();
                return;
            }
        }
        $v = new Zend_Validate_Alnum();
        if(!$v->isValid($password)){
            $msg = "パスワードを半角英数時で入力して下さい";
            $this->view->assign("result_msg", $msg);
            $this->_resetToken();
            return;
        }


        $result = false;
        try {
            $zdb_adapter = Zend_Registry::get("zdb_adapter");

            $db_prefix = Zend_Registry::get("config")->db->prefix;
            $table = $db_prefix."_user";
            $adapter = new Zend_Auth_Adapter_DbTable(
                $zdb_adapter,
                $table,
                //"login",
                $login_name,
                "passwd",
                "MD5(?)");
            $adapter->setIdentity($username);
            $adapter->setCredential($password);
            $result = $adapter->authenticate();
        }
        catch(Exception $e){
            $msg = "エラー:".$e->getMessage();
            $this->view->assign("result_msg", $msg);
            $this->_resetToken();
            return;
        }

        if($result && $result->isValid()) {
            $id = $result->getIdentity();
            //$row_obj = $adapter->getResultRowObject("user_id");
            //$sess_auth->user_id = $row_obj->user_id;
            $row_obj = $adapter->getResultRowObject(array("uid","login"));
            $sess_auth->user_id = $row_obj->uid;
            $sess_auth->user_name = $row_obj->login;


            // 自分のロール一覧を取得
            $manager = new Manager_User();
            $my_roles = $manager->getRolesByUid($sess_auth->user_id);
            foreach($my_roles as $r){
                $my_role_names[] = $r->get("name");
            }

            // 自分のロールで一番権限があるロール名をセットする
            $sess_auth->role = count($my_role_names) > 0 
                                ? $my_role_names[0] : "guest";

            $original_path = $request->getPost("original_path");

            if(isset($original_path)){
                $v = new Zend_Validate_Regex('/^\/[a-zA-Z0-9\/\-]*$/');
                if($v->isValid($original_path)){
                    $this->_redirect($original_path);
                    exit;
                }
            }

            $m = $this->getRequest()->getModuleName();
            $reg = Zend_Registry::getInstance();
            $root_url = $reg["config"]->site->root_url;
            if($m == "default"){
                $this->_redirect($root_url."/");
            } else {
                $this->_redirect($root_url."/".$m."/");
            }
        }
        else{
            $this->view->username = $this->getRequest()->getPost("username");
            $this->view->result_msg = "ID または PW が違います";
            $this->_resetToken();
        }
    }
    public function logoutAction()
    {
        Zend_Session::namespaceUnset("auth");
        $m = $this->getRequest()->getModuleName();
        $reg = Zend_Registry::getInstance();
        $root_url = $reg["config"]->site->root_url;
        if($m == "default"){
            $this->_redirect($root_url."/");
        } else {
            $this->_redirect($root_url."/".$m."/");
        }
    }
    protected function _resetToken()
    {
        $sess_form_opt = $this->_sessFormOpt;
        $token = md5(mt_rand());
        $this->view->assign("token", $token);
        $sess_form_opt->token = $token;
    }
}
