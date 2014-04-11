<?php
/**
 * User New Regist Form Validator
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

class Form_Validator_User_New extends Ao_Util_Validator
{
    protected $_controller;
    public function __construct(&$controller)
    {
        $this->_controller = $controller;
        $this->_config = Zend_Registry::get("config");
    }
    public function input(){
        // {{{
        if( getenv("REQUEST_METHOD") != "POST" ){
                return false;
        }
        $error = array();
        // 必須入力チェック
        if(!$this->_config->system->email_login){
            $error['login']['required'] = $this->postRequired('login');
        }
        $error['passwd']['required'] = $this->postRequired('passwd');
        $error['email']['required'] = $this->postRequired('email');

        // フォーマットチェック
        //$error['tel']['format']        = $this->postTelFormat('tel');
        if(@Zend_Registry::get("config")->system->email_login){
            // メールアドレスがログインIDならメールアドレスチェックする
            //$error['login']['format']      = $this->postEmailFormat('login');
        } else {
            $fmsg = "ログイン名には半角英数字と半角アンダーバー、半角アットマーク、半角ドット、半角ハイフンのみ使用できます";
            $error["login"]["format"] = $this->postRegexFormat("login", "/^[\-\.@_a-zA-Z0-9]+$/", $fmsg);
            $error['login']['length'] = $this->postStrlenMinMax('login', 6, 32);
        }
        $error['email']['format'] = $this->postEmailFormat('email');

        // ホワイトリストでチェック
        //$whitelist = array("AH","IT","KS","KT","SB","TP","SA","AC","DH","PH","YF","NB","CK","BO","LS","AD");
        //$error['sign']['unknown']      = $this->postWhiteListCheck('sign', $whitelist);

        // もし入力されていたらチェックする項目
        if( $this->postExistVal( 'passwd' ) ){
            $error['passwd']['length']             = $this->postStrlenMinMax('passwd', 6, 32);
            $fmsg = "パスワードには半角英数字と半角記号のみ使用できます";
            $error["passwd"]["format"] = $this->postRegexFormat( "passwd", "/^[!-~]+$/", $fmsg);
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
        $model = new Model_User();
        $select = $model->select();
        if($this->_config->system->email_login){
            $select->where("email = ?", $req->getPost("email"));
        } else {
            $select->where("login = ?", $req->getPost("login"));
        }
        $select->where("delete_flag is null or delete_flag = 0"); // 2012/02/15
        $vos = $model->fetchAll($select);

        $res = null;
        if($vos){
            $res = "既に登録済みです";
        }
        return $res;
    }
}
