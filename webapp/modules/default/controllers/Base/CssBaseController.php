<?php
/**
 * Default Css Base Controller
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
class CssBaseController extends Ao_Controller_AclAction
{
    protected $_registry;
    protected $_logger;

    public function init()
    {
        parent::init();
        $this->_registry = Zend_Registry::getInstance();
        $this->_logger = $this->_registry["logger"];

        // テンプレートのサフィックスを変更
        $this->_helper->viewRenderer->setViewSuffix('css');

        $webapp_dir = $this->_registry["webappDir"];
        $ldir = $webapp_dir."/modules/default/templates/css";
        $this->view->setLayoutDir($ldir);
    }
    public function indexAction()
    {
        $req = $this->getRequest();
        if($req->getQuery("l")){
            $layout = $req->getQuery("l");
            $layout_top = $this->_registry["webappDir"]."/layout";
            $path = $layout_top ."/".$layout."/style.css";
            if(file_exists($path)){
                $this->view->setLayoutDir($layout_top."/".$layout);
                echo $this->view->layout("style.css");
                exit;
            }
        }
    }
    public function commonjsAction()
    {
    }
    public function adminAction()
    {
    }
}
