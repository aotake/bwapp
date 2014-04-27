<?php
/**
 * Role New Regist Form Validator
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
 * @package       Ao.webapp.Form.Validator.Role
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Form_Validator_Role_New extends Ao_Util_Validator
{
    protected $_controller;
    public function __construct(&$controller)
    {
        $this->_controller = $controller;
    }
    public function input(){
        // {{{
        if( getenv("REQUEST_METHOD") != "POST" ){
                return false;
        }
        $error = array();
        // 必須入力チェック
        $error['rid']['required'] = $this->postRequired('rid');
        $error['name']['required'] = $this->postRequired('name');

        // フォーマットチェック
        //$error['tel']['format']        = $this->postTelFormat('tel');
        //$error['email']['format']      = $this->postEmailFormat('email');

        // ホワイトリストでチェック
        //$whitelist = array("AH","IT","KS","KT","SB","TP","SA","AC","DH","PH","YF","NB","CK","BO","LS","AD");
        //$error['sign']['unknown']      = $this->postWhiteListCheck('sign', $whitelist);

        // もし入力されていたらチェックする項目
        //if( $this->postExistVal( 'passwd' ) ){
        //    $error['passwd']['length']             = $this->postStrlenMinMax('address', 6, 32);
        //}
    
        $error["rid"]["integer"] = (null !== $this->postIsInteger("rid")) ? "整数値で入力して下さい" : null;

        $error["rid"]["registed"] = $this->isRegistedRid();
        $error["name"]["registed"] = $this->isRegistedName();

        $res['error']     = $error;
        $res['error_num'] = $this->countError( $error );
        return $res;
        // }}}
    }

    /*
     * 登録済みだったらメッセージを返す。未登録なら null を返す 
     */
    public function isRegistedRid()
    {
        $req = $this->_controller->getRequest();
        $val = $req->getPost("rid");

        $model = new Model_Role();
        $select = $model->select();
        $select
            //->where("delete_flag = 0 or delete_flag is null")
            ->where("rid = ?", $val)
            //->where("email = ?", $email)
            ;
        $vos = $model->fetchAll($select);

        $res = null;
        if($vos){
            $res = "既に登録済みです";
        }
        return $res;
    }
    public function isRegistedName()
    {
        $req = $this->_controller->getRequest();
        $val = $req->getPost("name");

        $model = new Model_Role();
        $select = $model->select();
        $select
            //->where("delete_flag = 0 or delete_flag is null")
            ->where("name = ?", $val)
            //->where("email = ?", $email)
            ;
        $vos = $model->fetchAll($select);

        $res = null;
        if($vos){
            $res = "既に登録済みです";
        }
        return $res;
    }
}
