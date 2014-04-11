<?php
/**
 * Group Form Validator
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
 * @package       Ao.webapp.Form.User
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Form_Validator_Group extends Ao_Util_Validator
{
    protected $_controller;
    public function __construct(&$controller)
    {
        $this->_controller = $controller;
    }
    /**
     * リスト形式のフォームチェック
     */
    public function listInput($key = "item")
    {
        $req = $this->_controller->getRequest();
        $params = $req->getParams();
        $lists = $params[$key];
        $_POST_ORIG = $_POST; // オリジナルの _POST データのバックアップ
        foreach($lists as $item)
        {
            $_POST = $item; // TODO: この仕様は変えたい
            $id = $item["id"];
            $errors[$id] = $this->input();
        }
        $total_error_num = 0;
        if($errors){
            foreach($errors as $e){
                $total_error_num += $e["error_num"];
            }
        }
        $errors["total_error_num"] = $total_error_num;
        // バックアップした_POST を元に戻す
        $_POST = $_POST_ORIG;
        return $errors;
    }
    /**
     * レコード単位のフォームチェック
     */
    public function input(){
        // {{{
        if( getenv("REQUEST_METHOD") != "POST" ){
                return false;
        }
        $error = array();
        // 必須入力チェック
        $error['id']['required'] = $this->postRequired('id');
        $error['uid']['required'] = $this->postRequired('uid');
        //$error['parent_id']['required'] = $this->postRequired('parent_id');
        $error['title']['required'] = $this->postRequired('title');
        $error['note']['required'] = $this->postRequired('note');
        $error['depth']['required'] = $this->postRequired('depth');
        $error['sort']['required'] = $this->postRequired('sort');
        $error['created']['required'] = $this->postRequired('created');
        $error['modified']['required'] = $this->postRequired('modified');
        $error['delete_flag']['required'] = $this->postRequired('delete_flag');

        // フォーマットチェック
        $error['id']['int'] = $this->postIsInteger('id');
        $error['uid']['int'] = $this->postIsInteger('uid');
        $error['parent_id']['int'] = $this->postIsInteger('parent_id');
        $error['depth']['int'] = $this->postIsInteger('depth');
        $error['sort']['int'] = $this->postIsInteger('sort');
        $error['delete_flag']['int'] = $this->postIsInteger('delete_flag');

        // ホワイトリストでチェック
        //$wlist = array("AH","IT","KS","KT");
        //$error['sign']['unknown'] = $this->postWhiteListCheck('sign', $wlist);

        // もし入力されていたらチェックする項目
        //if( $this->postExistVal( 'address' ) ){
        //    $error['address']['length'] = $this->postStrlenMinMax('address', 0, 32);
        //}

        //$error["global"]["registed"] = $this->isRegisted();

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
        $info_id = $req->getPost("info_id");
        $company = $req->getPost("company");
        $name = $req->getPost("name");
        //$email = $req->getPost("email");

        $entry = new Seminar_Model_Entry();
        $select = $entry->select();
        $select->where("delete_flag = 0 or delete_flag is null")
            ->where("info_id = ?", $info_id)
            ->where("company = ?", $company)
            ->where("name = ?", $name)
            //->where("email = ?", $email)
            ;
        $vos = $entry->fetchAll($select);

        $res = null;
        if($vos){
            $res = "既に登録済みです";
        }
        return $res;
    }

}
