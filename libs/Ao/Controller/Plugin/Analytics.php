<?php
/**
 * Open Web Analytics Plugin
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
class Ao_Controller_Plugin_Analytics extends Zend_Controller_Plugin_Abstract
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
 
    public function routeShutdown(Zend_Controller_Request_Abstract $reques)
    {
        //$this->_logger->log('MyAppPlugin::routeShutdown()',Zend_Log::DEBUG);
    }
 
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        //$this->_logger->log('MyAppPlugin::dispatchLoopStartup()',Zend_Log::DEBUG);
    }
 
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $mod = $this->getRequest()->getModuleName();
        $ctr = $this->getRequest()->getControllerName();
        $act = $this->getRequest()->getActionName();
        // site_id がある or admin_ip と同じアクセス元からのアクセスの場合処理する
        $site_id = null;
        if(isset($this->_config->site->analytics->site_id)){
            $site_id = $this->_config->site->analytics->site_id;
        }
        else if(
            isset($this->_config->site->analytics->admin_ip)
            && $this->_config->site->analytics->admin_ip == $_SERVER["REMOTE_ADDR"]
            && isset($this->_config->site->analytics->admin_site_id)
        ){
            $site_id = $this->_config->site->analytics->admin_site_id;
        }

        if($site_id){
            require_once($this->_config->site->analytics->root_path.'/owa_php.php');
            $owa = new owa_php();
            $owa->setSiteId($site_id);
            if(isset($this->_registry["modconf"]->$mod->module->title)){
                $owa->setPageTitle($this->_registry["modconf"]->$mod->module->title);
            } else {
                $owa->setPageTitle("$mod / $ctr / $act ");
            }
            $owa->trackPageView();
        }
    }
 
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
    }
 
    public function dispatchLoopShutdown()
    {
        //$this->_logger->log('MyAppPlugin::dispatchLoopShutdown()',Zend_Log::DEBUG);
        //$this->_logger->log('*********************',Zend_Log::DEBUG);
    }
}
