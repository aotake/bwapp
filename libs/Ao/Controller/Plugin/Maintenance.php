<?php
/**
 * Maintenance Mode Plugin
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

class Ao_Controller_Plugin_Maintenance extends Zend_Controller_Plugin_Abstract
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
        //$this->_logger->log('MyAppPlugin::routeStartup()',Zend_Log::DEBUG);
    }
 
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $m = $this->getRequest()->getModuleName();
        $c = $this->getRequest()->getControllerName();
        $a = $this->getRequest()->getActionName();

        // config が maintenance = true の場合はメンテナンス画面へ飛ばす
        // ただし次の場合は通常処理をする
        // 1. 管理者でログインしている場合
        // 2. logon コントローラへのアクセス
        // 3. config.ini の maintenance_ip に指定された IP からのアクセス
        // 4. 指定したユーザでのアクセス

        // メンテナンスモード
        $maintenance_mode = false;
        if(
            isset($this->_config->site->maintenance)
            && isset($this->_config->site->maintenance->mode)
        ){
            $maintenance_mode = $this->_config->site->maintenance->mode;
        }

        // モジュールメンテナンスモード
        $mod_maintenance_mode = false;
        if(
            isset($this->_registry["modconf"]->$m->module->maintenance)
            && isset($this->_registry["modconf"]->$m->module->maintenance->mode)
        ){
            $modconf = $this->_registry["modconf"]->$m;
            $mod_maintenance_mode = $modconf->module->maintenance->mode;

            // サイトがメンテもーでではなくてもモジュールがメンテモードなら
            // 当該モジュール時はメンテナンス画面にする。
            //  -> 論理和で実装
            $maintenance_mode = $maintenance_mode | $mod_maintenance_mode;
        }

        // モード、アクセス元、ユーザに応じて画面切り替え
        $ignore_controllers = array("logon", "loadcss");
        if ($maintenance_mode == true && !in_array($c,$ignore_controllers)) {
            // 自分の IP
            $myip = $_SERVER["REMOTE_ADDR"];

            // アクセス許可 IP
            if(isset($this->_config->site->maintenance->ip)){
                $maintenance_ips = explode("|", $this->_config->site->maintenance->ip);
                $is_maintenance_ip = in_array($myip, $maintenance_ips);
            } else {
                $maintenance_ips = null;
                $is_maintenance_ip = false; // 指定がなければどのIPからでもOK
            }

            // ユーザチェック
            $is_admin = false;
            $is_allow_user = false;
            $auth = new Zend_Session_Namespace("auth");
            if($auth->user_id){
                $user = new Manager_User();
                $role_vos = $user->getRolesByUid($auth->user_id);
                $role_1st = current($role_vos);
                if($role_1st instanceof Ao_Vo_Abstract && $role_1st->get("rid") == 1){
                    $is_admin = true;
                }

                // 管理者以外の許可されたユーザかをチェック
                if($is_admin){
                    $is_allow_user = true;
                } else if(isset($this->_config->site->maintenance->allow_user)){
                    $user_vo = $user->getUser($auth->user_id);
                    $allow_users = explode("|", $this->_config->site->maintenance->allow_user);
                    $is_allow_user = in_array($user_vo->get("login"), $allow_users);
                }
            }

            if(
                $c != "logon" &&                // ログオンコントローラではない、かつ
                $is_maintenance_ip == false &&  // メンテナンス IP ではない、かつ
                !($is_allow_user || $is_admin)  // 許可されたユーザでも管理者でもない
            ){
                $this->getResponse()->clearBody();
                $request->setModuleName('default');
                $request->setControllerName('maintenance');
                $request->setActionName('index');
            }
        }
    }
 
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        //$this->_logger->log('MyAppPlugin::dispatchLoopStartup()',Zend_Log::DEBUG);
    }
 
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
    }
 
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        //$this->_logger->log('MyAppPlugin::postDispatch(). count:' . $this->_dispatchCount,Zend_Log::DEBUG);
    }
 
    public function dispatchLoopShutdown()
    {
        //$this->_logger->log('MyAppPlugin::dispatchLoopShutdown()',Zend_Log::DEBUG);
        //$this->_logger->log('*********************',Zend_Log::DEBUG);
    }
}
