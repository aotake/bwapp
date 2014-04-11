<?php
/**
 * User New Regist Form
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
 * @package       Ao.webapp.Form.User
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Form_User_New
{
    protected $_controller;
    protected $_registry;
    protected $_zv;
    protected $_param;

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
        $validator = new Form_Validator_User_New($this->_controller);
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
        $uid = $req->getParam("uid");
        $name = $req->getParam("name");
        $login = $req->getParam("login");
        $passwd = $req->getParam("passwd");
        $email = $req->getParam("email");
        $note = $req->getParam("note");
        return array(
            "uid" => $this->hiddenUid($uid),
            "name" => $this->textName($name),
            "login" => $this->textLogin($login),
            "passwd" => $this->textPassword($passwd),
            "email" => $this->textEmail($email),
            "note" => $this->tareaNote($note),
        );
    }

    public function hiddenUid()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("uid");
        $name = "uid";
        $attr = array(
            "id" => "uid",
            "class" => "uid",
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

    public function textLogin()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("login");
        $name = "login";
        $attr = array(
            "id" => "login",
            "class" => "login",
            "size" => 20,
        );
        return $this->_zv->formText($name, $default, $attr);
    }

    public function textPassword()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("passwd");
        $name = "passwd";
        $attr = array(
            "id" => "passwd",
            "class" => "passwd",
            "size" => 20,
        );
        return $this->_zv->formPassword($name, $default, $attr);
    }

    public function textEmail()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("email");
        $name = "email";
        $attr = array(
            "id" => "email",
            "class" => "email",
            "size" => 50,
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

