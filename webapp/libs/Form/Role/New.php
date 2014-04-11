<?php
/**
 * Role New Regist Form
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
 * @package       Ao.webapp.Form.Role
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Form_Role_New
{
    private $_controller;
    private $_registry;
    private $_zv;
    private $_param;

    protected $_errors;
    protected $_error_num;

    public function __construct(&$controller)
    {
        $this->_controller = $controller;
        $this->_zv = new Ao_View();
        $this->_param = array();
        $this->_registry = Zend_Registry::getInstance();
    }

    public function validate()
    {
        $validator = new Form_Validator_Role_New($this->_controller);
        $chk = $validator->input();
        $this->_errors = $chk["error"];
        $this->_error_num = $validator->countError($chk["error"]);
    }

    public function errors()
    {
        return $this->_errors;
    }
    public function errorNum()
    {
        return $this->_error_num;
    }

    public function setParams($params = array())
    {
        $req = $this->_controller->getRequest();
        foreach($params as $k => $v){
            $req->setParam($k, $v);
        }   
    } 

    public function formElements()
    {
        $req = $this->_controller->getRequest();
        $rid = $req->getParam("rid");
        $name = $req->getParam("name");
        $note = $req->getParam("note");
        return array(
            "rid" => $this->textRid($rid),
            "name" => $this->textName($name),
            "note" => $this->tareaNote($note),
        );
    }

    public function textRid()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("rid");
        $name = "rid";
        $attr = array(
            "id" => "rid",
            "class" => "rid",
            "size" => 3,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function hiddenRid()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("rid");
        $name = "rid";
        $attr = array(
            "id" => "rid",
            "class" => "rid",
        );
        return $this->_zv->formHidden($name, $default);
    }

    public function textName()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("name");
        $name = "name";
        $attr = array(
            "id" => "name",
            "class" => "name",
            "size" => 20,
        );
        return $this->_zv->formText($name, $default, $attr);
    }

    public function tareaNote()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("note");
        $name = "note";
        $attr = array(
            "id" => "note",
            "class" => "ckeditor",
            "cols" => 45,
            "rows" => 10,
        );
        return $this->_zv->formTextarea($name, $default, $attr);
    }

}

