<?php 
/**
 * User Manager
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
 * @package       Ao.webapp.Manager
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Manager_User
 *
 * user テーブルの管理（xoops_users ではない)
 */
class Manager_User extends Manager
{
    var $sAuth;
    protected $search_target_columns = array("name", "email");
    
    public function __construct(&$controller = null)
    {
        $this->_controller = $controller;
        $this->sAuth = new Zend_Session_Namespace("auth");;
    }

    /**
     * getUser
     *
     * 指定 uid または実行ユーザの uid の Vo を返す
     *
     * @param $id ユーザID
     * @return Array of UserVo
     */
    public function getUser($id = null)
    {
        if($id == null && $this->sAuth->user_id){
            $id = $this->sAuth->user_id;
        }

        if(!$id){
            return false;
        }

        $model = new Model_User();
        $select = $model->select();
        $select->where("uid = ?", $id);
        $res = $model->fetchAll($select);
        if($res){
            return current($res);
        } else {
            return null;
        }
    }
    /**
     * gets
     *
     * ユーザ Vo の配列を返す
     *
     * @param $limit 取得件数
     * @param $offset 取得開始位置
     * @return Array of UsreVo
     */
    public function gets($id = null)
    {
        if($id == null && $this->sAuth->user_id){
            $id = $this->sAuth->user_id;
        }

        if(!$id){
            return false;
        }

        $model = new Model_User();
        $select = $model->select();
        $select->order("uid desc");
        return $model->fetchAll($select);
    }
    /**
     * ページ単位のVoの配列取得
     *
     * 引数があれば対応する Vo を唯一の要素とする配列を返す。引数がなければ
     * 全てのレコードの Vo を要素とする配列を返す。
     *
     * @param int $limit １ページの表示件数
     * @param int $offset 表示オフセット
     * @param array $orders ソート条件の配列
     * @param string $keyword キーワード文字列
     * @return array <{$ModuleName}>_Vo_<{$item.tableName}>の配列
     */
    public function getPage($limit = 20, $offset = 0, $orders = array("id asc"), $keyword = null)
    {
        $model = new Model_User();
        $select = $model->select();
        $select->where("delete_flag = 0 or delete_flag is null");
        // ソート条件の正規化(?)
        if($orders == null){
            $orders = array("uid desc");
        } else if (is_string($orders)) {
            $orders = array($orders);
        }
        // ソート条件登録
        foreach($orders as $order){
            $select->order($order);
        }
        if($keyword){
            if(is_numeric($keyword)){
                $cond[] = "uid = ".(int)$uid;
            }
            //$keyword == Ao_Util_TextSanitizer::sanitize($keyword);
            foreach($this->search_target_columns as $col){
                $cond[] = $col." like '%".$keyword."%'";
            }   
            $select->where(implode(" or ", $cond));
        }
        $select->limit($limit, $offset);
        return $model->fetchAll($select);
    }
    /**
     * データ件数
     *
     * キーワードが指定されているときは、キーワードを含むデータの件数を、
     * キーワードが無い時は全てのデータ件数を返す。
     *
     * @param string $keyword キーワード文字列
     * @return int 登録データ件数
     */
    public function getCount($keyword = null)
    {
        $count = 0;
        $model = new Model_User();
        $select = $model->select()
            ->setIntegrityCheck(false)
            ->from($model->name(), 'count(*) as count');
        $select->where("delete_flag = 0 or delete_flag is null");
        if($keyword){
            foreach($this->search_target_columns as $col){
                    $cond[] = $col." like '%".$keyword."%'";
            }   
            $select->where(implode(" or ", $cond));
        }
        $res = $model->fetchAll($select,1);
        if($res){
            $count = $res[0]["count"];
        }
        return $count;
    }


    /**
     * getByEmail()
     *
     * @param $email メールアドレス
     * @return vo
     */
    public function getVoByEmail($email)
    {
        $model = new Model_User();
        $select = $model->select()
            ->where("email = ?", $email)
            ;
        $vos = $model->fetchAll($select);
        if($vos){
            return current($vos);
        } else {
            return null;
        }
    }

    /**
     * getUsersByEmail()
     *
     * メールアドレスにマッチするレコード全てを返す。
     *
     * @param $email メールアドレス
     * @param $ignore_delete_flag delete_flag を無視するかのフラグ
     * @return array Vo_User の配列
     */
    public function getUsersByEmail($email, $ignore_delete_flag = false)
    {
        $model = new Model_User();
        $select = $model->select()
            ->where("email = ?", $email)
            ;
        if(false == $ignore_delete_flag){
            $select->where("delete_flag = 0 or delete_flag is null");
        }
        $vos = $model->fetchAll($select);
        if($vos){
            return $vos;
        } else {
            return null;
        }
    }

    /**
     * getVo
     *
     */
    public function getVo($_params = null)
    {
        $model = new Model_User();
        return $model->getVo($_params);
    }

    /**
     * getCelebUsers
     *
     * 著名人ユーザの取得
     *
     * @return Array of UserVo
     */
    public function getCelebUsers()
    {
        if($this->_controller == null){
            throw new Zend_Exception("User Manager Error: no controller");
        }
        $req = $this->_controller->getRequest();
        $model = new Model_User();
        $model_ur = new Model_UserRole();
        $select = $model->select();
        $select->setIntegrityCheck(false)
            ->from(array("u" => $model->name()), array("*"))
            ->joinLeft(
                array("ur" => $model_ur->name()),
                "u.uid = ur.uid",
                array(""))
            ->where("ur.rid = ?", Manager_Role::ROLE_ID_CELEB)
            ->order("u.uid asc")
            ;
        $res = $model->fetchAll($select, true);
        if($res){
            return $this->_assignCommonVos($res);
        } else {
            return null;
        }
    }

    public function save($vo, $tr_flag = true)
    {
        $model = new Model_User();
        if($tr_flag) Manager::beginTransaction();
        try{
            $res = $model->save($vo);
            if($tr_flag) Manager::commit();
        } catch(Exception $e){
            if($tr_flag) Manager::rollback();
            throw $e;
        }
        return $res;
    }

    /**
     * getRolesByUid
     *
     * ロールIDが若い順（権限がある順）にソートしてロールリストを取得
     *
     * @param $uid ユーザID
     * @return Array of CommonVo 
     */
    public function getRolesByUid($uid = null)
    {
        $model = new Model_Role();
        $model_ur= new Model_UserRole();
        $select = $model->select();
        $select->setIntegrityCheck(false)
            ->from(array("r" => $model->name()), array("*"))
            ->joinLeft(
                array("ur" => $model_ur->name()),
                "r.rid = ur.rid",
                array("*"))
            ->where("ur.uid = ?", (int)$uid)
            ->order("r.rid asc")
            ;
        $res = $model->fetchAll($select, true);
        if($res){
            return $this->_assignCommonVos($res);
        } else {
            return null;
        }
    }

    /**
     * getEncPassword
     *
     * XoopsCube は md5() してるだけだったので SALT は使わない
     *
     * TODO: パスワードに SALT を追加する
     * http://www.php.net/manual/ja/faq.passwords.php#faq.passwords.fasthash
     *
     * @param string 生パスワード文字列
     * @return string 暗号化パスワード文字列
     */
    public function getEncPassword($str)
    {
        //$reg = Zend_Registry::getInstance();
        //$dbconf = $reg["config"]->db;
        //$salt = (string)$dbconf->salt;
        //return md5($salt.$str);
        /*
        switch(isset(Zend_Registry::get("config")->system->password_enctype)){
        case "crypt":
            $salt = "pt";
            if(isset(Zend_Registry::get("config")->system->password_salt)){
                $salt = Zend_Registry::get("config")->system->password_salt;
            }
            $pw = crypt($str, $salt);
            break;
        case "sha1":
            $pw = sha1($str);
            break;
        case "md5":
        default:
        */
            $pw = md5($str);
        /*
            break;
        }
        */
        return $pw;
    }

    /**
     * genPassword
     *
     * @param int $length パスワード長
     * @return string $password パスワード文字列
     */
    public function genPassword($length = 6)
    {
        $chars = 'abcdefghjkmnpqrstuvwxyz'
               . 'ABCDEFGHJKLMNPQRSTUVWXYZ'
               . '23456789!=_-@;';
        $charsLength = strlen($chars);
        $password = null;
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $charsLength - 1);
            $password .= $chars{$num};
        }
        return $password;
    }

    public function delete($uid)
    {
        $model = new Model_User();
        $model->del($uid);
    }

    /**
     * ユーザアカウントの論理削除
     *
     * Zend_Auth では認証するログインID文字列が重複しているレコードがあると
     * 認証結果が常に false となるため login, email の元の文字列の前に "@_retire_@" をつける
     * パスワードはとりあえず最初の１０文字を切り取って後ろにつなげた物に変えておく【復元時注意】。
     */
    public function deleteByUpdate($uid)
    {
        $user_vo = $this->getUser($uid);
        $passwd_prev = substr($user_vo->get("passwd"), 0, 10);
        $passwd_post = substr($user_vo->get("passwd"), 10, 32);
        $t_passwd = $passwd_post.$passwd_prev;
        $user_vo->set("passwd", $t_passwd);
        $user_vo->set("login", "@_retire_@".$user_vo->get("login"));
        $user_vo->set("email", "@_retire_@".$user_vo->get("email"));
        $user_vo->set("delete_flag", 1);
        $this->save($user_vo);
    }

    public function sendPasswordNotify($vo, $new_pw)
    {
        $reg = Zend_Registry::getInstance();
        $req = $this->_controller->getRequest();
        $view =& $this->_controller->view;
        $m = $req->getModuleName();
        $c = $req->getControllerName();
        $view->email = $vo->get("email");
        $view->passwd = $new_pw;
        $mail_body = $view->fetch($c."/resetpass.mail");
        $config = $reg["config"];
        $to = $vo->get("email");
        $subject = "パスワード再発行";
        $from = 'aotake@bmath.org';
        mb_language("ja");
        mb_internal_encoding($config->site->charset);
        mb_send_mail(
            $to,
            $subject,
            $mail_body,
            "From: ".$from);
    }
}
