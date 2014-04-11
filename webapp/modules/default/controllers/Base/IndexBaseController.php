<?php
/**
 * Default Index Base Controller
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

class IndexBaseController extends Ao_Controller_AclAction
{
    protected $_config;
    protected $_logger;

    public function init()
    {
        parent::init();
        $registry = Zend_Registry::getInstance();
        $this->_logger = $registry["logger"];
        $this->_config = $registry["config"];
    }
    public function indexAction()
    {
        // dummy data
        $this->view->topics = array(
            array(
                "category" => array("id" => 1, "title" => "お知らせ"),
                "title" => "これはテストです",
                "author" => array("id" => "1", "name" => "admin"),
                "published" => mktime(0,0,0, date("m"), date("d"), date("Y")),
                "body" => "Twitter と Facebook をつかって活性化したいですね",
            ),
            array(
                "category" => array("id" => 1, "title" => "お知らせ"),
                "title" => "これはテストです",
                "author" => array("id" => "1", "name" => "admin"),
                "published" => mktime(0,0,0, date("m"), date("d"), date("Y")),
                "body" => "Twitter と Facebook をつかって活性化したいですね",
            ),
            array(
                "category" => array("id" => 1, "title" => "お知らせ"),
                "title" => "これはテストです",
                "author" => array("id" => "1", "name" => "admin"),
                "published" => mktime(0,0,0, date("m"), date("d"), date("Y")),
                "body" => "Twitter と Facebook をつかって活性化したいですね",
            ),
            array(
                "category" => array("id" => 1, "title" => "お知らせ"),
                "title" => "これはテストです",
                "author" => array("id" => "1", "name" => "admin"),
                "published" => mktime(0,0,0, date("m"), date("d"), date("Y")),
                "body" => "Twitter と Facebook をつかって活性化したいですね",
            ),
        );
    }
}
