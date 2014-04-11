<?php
/**
 * View Manager
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

class Manager_View extends Manager {
    private $controller;
    public function __construct(&$controller)
    {
        $this->controller =& $controller;
    }
    public function fetchTemplate($resource)
    {
        $reg = Zend_Registry::getInstance();
        $view =& $this->controller->view;
        $orig_path = $view->getScriptPath();
        if(preg_match("/^(.+)::(.+)$/", $resource, $match)){
            $tmp_path = $reg["webappDir"]."/modules/".$match[1]."/templates";
            $view->setScriptPath($tmp_path);
            $resource = $match[2];
        }
        if(file_exists($tmp_path."/".$resource)){
            $html = $view->fetch($resource);
        } else {
            $html = null;
            trigger_error("not found templates: $resource");
        }
        $view->setScriptPath($orig_path);
        return $html;
    }
}
