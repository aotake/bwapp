<?php
/**
 * User Edit Form Validator
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
 * @package       Ao.webapp.Form.Validator.User
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Form_Validator_User_Edit extends Form_Validator_User_New
{
    protected $_controller;
    public function __construct(&$controller)
    {
        $this->_controller = $controller;
        parent::__construct($controller);
    }
    public function input(){
        // {{{
        if( getenv("REQUEST_METHOD") != "POST" ){
                return false;
        }
        $error = array();
        // 必須入力チェック
        $error['login']['required'] = $this->postRequired('login');
        $error['email']['required'] = $this->postRequired('email');

        // フォーマットチェック
        //$error['tel']['format']        = $this->postTelFormat('tel');
        if(@Zend_Registry::get("config")->system->email_login){
            // メールアドレスがログインIDならメールアドレスチェックする
            //$error['login']['format']      = $this->postEmailFormat('login');
        } else {
            $fmsg = "ログイン名には半角英数時と半角アンダーバーのみ使用できます";
            $error["login"]["format"] = $this->postRegexFormat("login", "/^[_a-zA-Z0-9]+$/", $fmsg);
            // admin 以外はアカウント文字列長チェック
            if($this->_controller->getRequest()->getParam("login") != "admin") {
                $error['login']['length'] = $this->postStrlenMinMax('login', 6, 32);
            }
        }
        $error['email']['format']      = $this->postEmailFormat('email');

        // ホワイトリストでチェック
        //$whitelist = array("AH","IT","KS","KT","SB","TP","SA","AC","DH","PH","YF","NB","CK","BO","LS","AD");
        //$error['sign']['unknown']      = $this->postWhiteListCheck('sign', $whitelist);

        // もし入力されていたらチェックする項目
        if( $this->postExistVal( 'passwd' ) ){
            $error['passwd']['length']             = $this->postStrlenMinMax('passwd', 6, 32);
            // http://nplll.com/archives/2005/09/post_706.php
            $error["passwd"]["format"] = $this->postInvalidRegexFormat("passwd", "/\s+/", $errmsg = "空白文字は使えません");
            $fmsg = "パスワードには半角英数字と半角記号のみ使用できます";
            $error["passwd"]["format"] = $this->postRegexFormat("passwd", "/^[!-~]+$/", $fmsg);

            if(isset($_POST["passwd_confirm"])){
                if($_POST["passwd"] != $_POST["passwd_confirm"]){
                    $fmsg = "パスワードが一致しません。";
                    $error["passwd"]["format"] = $fmsg;
                    $error["passwd_confirm"]["format"] = $fmsg;
                }
            }

        }

        if($this->_config->system->email_login){
            $error["email"]["registed"] = $this->isRegisted();
        } else {
            $error["login"]["registed"] = $this->isRegisted();
        }


        $res['error']     = $error;
        $res['error_num'] = $this->countError( $error );
        return $res;
        // }}}
    }

    /*
     * 登録済みだったらメッセージを返す。未登録なら null を返す 
     */
    public function isRegisted()
    {
        $req = $this->_controller->getRequest();

        // uid がなければ自身の UID を埋める
        if($req->getParam("uid")){
            $uid = $req->getParam("uid");
            // DB のデータを取得する
            $model = new Model_User();
            $select = $model->select();
            $select->where("uid = ?", $req->getParam("uid"));
            $_data = $model->fetchAll($select);
            $userVo = current($_data);
        } else {
            $userVo = $this->_controller->getUserInfo();
        }

        $model = new Model_User();
        $select = $model->select();
        if($this->_config->system->email_login){
            // DB と POST された email が一緒なら null を返す
            if($userVo->get("email") == $req->getParam("email")){
                return null;
            }
            // そうでなければ POST された値でデータが引っかかるか試す
            $select->where("email = ?", $req->getParam("email"));
        } else {
            // DB と POST された login が一緒なら null を返す
            if($userVo->get("login") == $req->getParam("login")){
                return null;
            }
            // そうでなければ POST された値でデータが引っかかるか試す
            $select->where("login = ?", $req->getParam("login"));
        }
        $vos = $model->fetchAll($select);

        $res = null;
        if($vos){
            $res = "既に登録済みです";
        }
        return $res;
    }
}
