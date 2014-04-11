<?php
/**
 * Default Loadcss Controller
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
 * @package       Ao.modules.admin.Controller
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author     Takeshi Aoyama (aotake@bmath.org)
 */
// モジュールの CSS をロードするコントローラ
class LoadcssController extends Ao_Controller_Action
{
    protected $_logger;
    private $upload_dir;

    public function init()
    {
        parent::init();
        $registry = Zend_Registry::getInstance();
        $this->_logger = $registry["logger"];

        $r = Zend_Registry::getInstance();
        $this->registry = $r;
    }
    public function indexAction()
    {
        $req = $this->getRequest();
        if($req->getQuery("m") == ""){
            $this->_logger->debug(__METHOD__.", remote_addr=".$_SERVER["REMOTE_ADDR"].", no module were specified.");
            exit;
        }
        $module = $req->getQuery("m");
        if($module == "default" && isset($this->registry["config"]->system->default_module)){
            $module = $this->registry["config"]->system->default_module;
        }
        $css_default = $this->registry["webappDir"] ."/modules/". $module."/css/style.css";
        $css_custom  = $this->registry["webappDir"] ."/modules/". $module."/css/custom/style.css";

        if(file_exists($css_custom)){
            $css_path = $css_custom;
        } else if(file_exists($css_default)) {
            $css_path = $css_default;
        } else {
            exit;
        }

        header("Content-Type: text/css");
        $fp = fopen( $css_path, "r" );
        if( !$fp ){
            $this->_logger->warn("cannot open css file: module = $module, expected path = modules/$module/css/style.css");
        }       
        else{
            print fread($fp, filesize($css_path));
        }
        fclose( $fp );
        exit;
    }
}
