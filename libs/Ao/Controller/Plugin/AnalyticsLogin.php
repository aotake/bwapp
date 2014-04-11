<?php
/**
 * Open Web Analytics Auto-Login Plugin
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
 * @package       Ao.Controller.Plugin
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Ao_Controller_Plugin_AnalyticsLogin extends Zend_Controller_Plugin_Abstract
{
    private $_registry = null;
    private $_logger = null;
    private $_config = null;
    private $_dispatchCount = 0;

    public function __construct()
    {
        $this->_registry = Zend_Registry::getInstance();
        $this->_logger = $this->_registry["logger"];
        $this->_config = $this->_registry["config"];
    }

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
    }
 
    public function routeShutdown(Zend_Controller_Request_Abstract $reques)
    {
    }
 
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
    }
 
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
    }
 
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
return false; /// 使わない
        // サイトログインユーザでは無い場合は自動ログインしない
        $auth = new Zend_Session_Namespace("auth");
        if(!$auth->user_id){
            return;
        }
        // サイト管理者ユーザでなければ自動ログインしない
        $user = new Manager_User();
        $role_vos = $user->getRolesByUid($auth->user_id);
        $role_1st = current($role_vos);
        if($role_1st == "" || $role_1st->get("rid") != 1){
            return;
        }
        // アクセス元 IP が設定されている場合、設定IP 以外の IP からのアクセスではログインさせない
        if(isset($this->_conifg->site->analytics->admin_ip) && $this->_conifg->site->analytics->admin_ip == $_SERVER["REMOTE_ADDR"]){
            return;
        }
        if(isset($this->_config->site->analytics->user)){
// -- autologin for owa --

// 注意：下記のコードを実行すると、OWA 側でログアウトしてもクッキーが削除されず何時までもログイン出来てしまう。
// そのため現在はこのプラグインは使用不可とする。

            $user_id = $this->_config->site->analytics->user;
            $password = owa_lib::encryptPassword($this->_config->site->analytics->pass);
            $cookie_domain = owa_coreAPI::getSetting('base', 'cookie_domain');
            //if(isset($this->_config->site->analytics->cookie_path)){
            //    $cookie_path = $this->_config->site->analytics->cookie_path;
            //} else {
                $cookie_path = "/";
            //}

            // see: http://www.ark-web.jp/sandbox/wiki/240.html
            header(sprintf('P3P: CP="%s"', owa_coreAPI::getSetting('base', 'p3p_policy')));
            setcookie('owa_u', $user_id, time()+3600*24*365*10, $cookie_path, $cookie_domain);
            setcookie('owa_p', $password, time()+3600*24*30, $cookie_path, $cookie_domain);
// -- /autologin for owa --
        }
    }
 
    public function dispatchLoopShutdown()
    {
    }
}
