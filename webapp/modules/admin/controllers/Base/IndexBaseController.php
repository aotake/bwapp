<?php
/**
 * Admin Index Base Controller
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
class Admin_IndexBaseController extends Ao_Controller_AclAction
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
        // システム情報の取得
        $system = array(
            "apache" => apache_get_version(),
            //"mysql" => mysql_get_server_info(),
            "php" => PHP_VERSION,
            "zend" => Zend_Version::VERSION,
            //"zend_latest" => Zend_Version::getLatest(),
            "aflib" => Ao_Version::VERSION,
        );
        //if(!$system["mysql"]){
        //    $tmp = shell_exec("/usr/bin/mysql --version");
        //    preg_match("/.+(Ver [ a-zA-Z0-9\.]+),.*/", $tmp, $m);
        //    $system["mysql"] = $m[1];
        //}

        // モジュール情報の取得
        $registry = Zend_Registry::getInstance();

        // _SERVER からほしいものだけ
        $keys = array(
            "APPLICATION_ENV",
            "HTTP_HOST",
            "SERVER_NAME",
            "SERVER_ADDR",
            "SERVER_PORT",
            "REMOTE_ADDR",
        );
        foreach($keys as $key){
            $server[$key] = $_SERVER[$key];
        }

        // アサイン
        $this->view->system = $system;
        $this->view->acl = $registry["config"]->acl->toArray();
        $this->view->env = $server;

        // もし admin 以外のモジュールを管理トップに使う設定があれば移動する
        if(!empty($this->_config->system->admin_top_module)){
            $module = $this->_config->system->admin_top_module;
            return $this->_forward("index", "index", $module);
        }
    }

}
