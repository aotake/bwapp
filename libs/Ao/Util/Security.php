<?php
/**
 * Security Filter Utility
 *
 * PHP 5
 *
 * Copyright (c) 2011-2012 Bmath Web Application Platform Project.(http://bmath.jp)
 * All Rights Reserved.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @copyright     Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
 * @link          http://bmath.jp Bmath Web Application Platform Project
 * @package       Ao.Util
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * 参考：
 * 1. XOOPS の GIJOE 氏による Protector モジュール
 * 2. GIJOE 氏の著書「PHPサイバーテロの技法―攻撃と防御の実際」
 * 3. 徳丸浩氏の著書「体系的に学ぶ 安全なWebアプリケーションの作り方　脆弱性が生まれる原理と対策の実践」
 * 4. ASCII 文字コード：IT用語辞典(http://e-words.jp/p/r-ascii.html)
 */
class Ao_Util_Security
{
    static private $instance;
    private $bad_globals;
    private $policy;

    private function __construct()
    {
        if (isset(self::$instance))
        {
            throw new Exception("複数生成できません：".get_called_class());
        }
        static::init();
    }

    public function getInstance()
    {
        if(!isset($instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        $this->policy = array(
            'controll_code' => "sanitize",
            'sql_union' => "sanitize",
            'double_dot' => "log",
            'cmd_injection' => "abort",
        );

        // TODO:ポリシーファイルがあればロードして初期値を上書き

        $this->bad_globals = array('GLOBALS' , '_SESSION' , 'HTTP_SESSION_VARS' , '_GET' , 'HTTP_GET_VARS' , '_POST' , 'HTTP_POST_VARS' , '_COOKIE' , 'HTTP_COOKIE_VARS' , '_SERVER' , 'HTTP_SERVER_VARS' , '_REQUEST' , '_ENV' , '_FILES');

    }
    public function check()
    {
        $this->check_r($_GET, "_GET");
        $this->check_r($_POST, "_POST");
        $this->check_r($_COOKIE, "_COOKIE");
    }
    /**
     * 再帰的に初期チェック
     */
    public function check_r($val, $key)
    {
        if(is_array($val)){
            foreach($val as $k => $v){
                if(in_array($k, $this->bad_globals, true)){
                    Zend_Registry::get("logger")->warn("[SECURITY Plugin] bad global var found (".$k."), remote_address=".@$_SERVER["REMOTE_ADDR"]);
                }
                // サニタイズのとき "~~" で explode() して配列の要素を指定できるよう
                // 値に対するキーを "~~" でつないで文字列にする。
                $this->check_r($v, $key."~~".$k);
            }
        } 
        else {
            // TODO: 拒否IP (Zend_Config_Ini利用)
        
            // Null Byte チェック
            $this->_check_controll_code($val, $key);

            // Sql Injection チェック
            $this->_check_sql_union($val, $key);

            // Command Injection
            if(preg_match("/^_GET~~([^~]+)$/", $key)){ // GET のみチェックでよい？
                $this->_check_double_dot($val, $key);
            }

            // XSS
        }
    }

    /**
     * Null Byte 含め、コントロールコードのチェック
     */
    private function _check_controll_code($v, $key)
    {
        if(strstr($v, chr(0))) {
            $v_sani = str_replace( chr(0), "[NULLBYTE]", $v);
            Zend_Registry::get("logger")->warn("[SECURITY Plugin] NULL byte was found. key = ".$key.", value=".$v_sani.", remote_address=".@$_SERVER["REMOTE_ADDR"]);
            $var = $this->_sanitizeControllCode($key);
            return false;
        }
        else if(preg_match('/[\x00-\x1f\x7f]/', $v)){
            Zend_Registry::get("logger")->warn("[SECURITY Plugin] controll code was found. key = ".$key.", remote_address=".@$_SERVER["REMOTE_ADDR"]);
            $var = $this->_sanitizeControllCode($key);
            return false;
        }
        return true;
    }
    /**
     * UNION SELECT チェック
     */
    private function _check_sql_union($v, $key)
    {
        if(preg_match('/\sUNION\s+(ALL|SELECT)/i', $v)){
            Zend_Registry::get("logger")->warn("[SECURITY Plugin] maybe sql injection string(".$v."), request_uri=".@$_SERVER["REQUEST_URI"].", remote_address=".@$_SERVER["REMOTE_ADDR"]);

            if($this->policy["sql_union"] == "sanitize"){
                $var = $this->_sanitizeWithPattern("/\s(UNION)\s/i", " UNI-ON ", $key);
                Zend_Registry::get("logger")->warn("[SECURITY Plugin] sanitize => ".$var);
            }
            else if($this->policy["sql_union"] == "abort") {
                throw new Zend_Exception("Abort(by SQL Injection, union select)");
            }
            return false;
        }
        return true;
    }
    /**
     * コントロールコードの除去
     */
    private function _sanitizeControllCode($key, $replace = "")
    {
        $tmp = explode("~~", $key);
        foreach($tmp as $i => $name){
            if($name == "_GET"){
                $var =& $_GET;
            } else {
                $var =& $var[$name];
            }
        }
        $var = preg_replace('/[\x00-\x20\x22\x27]/', $replace ,$var);
        return $var;
    }
    
    /**
     * チェック用キーから変数を取り出す
     */
    private function _sanitizeWithPattern($pattern, $trans, $key)
    {
        $tmp = explode("~~", $key);
        foreach($tmp as $i => $name){
            if($name == "_GET"){
                $var =& $_GET;
            } else {
                $var =& $var[$name];
            }
        }
        $var = preg_replace($pattern, $trans, $var);
        return $var;
    }

    /**
     * ダブルドットチェック(../ や ../../ など)
     */
    private function _check_double_dot($v, $key)
    {
        if(substr(trim($v), 0, 3) == "../" || strstr( $v, "../../" )){
            Zend_Registry::get("logger")->warn("[SECURITY Plugin] directory traversal was found. key = ".$key.", value=".$v.", remote_address=".@$_SERVER["REMOTE_ADDR"]);
            return false;
        }
        return true;
    }

    /**
     * リファラチェック
     *
     * REFERER を参照して、自サイト内からのアクセスなら true、
     * そうでない場合は false を返す。
     *
     */
    static public function refCheck($root_url = null)
    {
        if(!$root_url){
            $root_url = Zend_Registry::get("config")->site->root_url;
        }
        if(preg_match('#\A'.$root_url.'/#', @$_SERVER["HTTP_REFERER"]) !== 1) {
            Zend_Registry::get("logger")->warn("[SECURITY Plugin] invalid referer: mod = $m, ctr = $c, act = $a, ".@$_SERVER["HTTP_REFERER"].", ".$_SERVER["REMOTE_ADDR"]);
            return false;
        }
        return true;
    }
    /**
     * トークン HTML 出力
     */
    static public function tokenHtml()
    {
        return '<input type="hidden" name="token" value="'.htmlspecialchars(session_id(), ENT_COMPAT, 'UTF-8').'" />';
    }
    /**
     * トークンチェック
     *
     * @param Zend_Http_Request $req リクエストオブジェクト
     * @return boolean
     */
    static public function checkToken($req = null)
    {
        if($req){
            $front = Zend_Controller_Front::getInstance();
            $req = $front->getRequest();
        }
        $m = $req->getModuleName();
        $c = $req->getControlelrName();
        $a = $req->getActionName();
        if( session_id() !== $req->getParam("token") ){
            Zend_Registry::get("logger")->warn("invalid token: mod = $m, ctr = $c, act = $a, ".@$_SERVER["HTTP_REFERER"].", ".$_SERVER["REMOTE_ADDR"]);
            return false;
        }
        return true;
    }

}
