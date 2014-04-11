<?php
/**
 * User Form
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
 * @package       Ao.webapp.Form
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Form_User
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
        $validator = new Rekishikan_Form_Validator_Profile($this->_controller);
        // $chk = $validator->listInput(); // リスト形式フォーム用
        $chk = $validator->input(); // カード形式フォーム用
        $this->_errors = $chk["error"];
        //$this->_error_num = $chk["total_error_num"]; //リスト形式用
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
        return array(
            "uid" => $this->hiddenUid(),
            "name" => $this->textName(),
            "login" => $this->textLogin(),
            "passwd" => $this->textPasswd(),
            "passwd_new" => $this->textPasswdNew(),
            "passwd_confirm" => $this->textPasswdCOnfirm(),
            "email" => $this->hiddenEmail(),
        );
    }

    public function hiddenUid()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("uid");
        $name = "uid";
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
            "size" => 30,
        );
    }
    public function textLogin()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("login");
        $name = "login";
        $attr = array(
            "id" => "login",
            "class" => "login",
            "size" => 30,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function textEmail()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("email");
        $name = "email";
        $attr = array(
            "id" => "email",
            "class" => "email",
            "size" => 30,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function textPasswd()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("passwd");
        $name = "passwd";
        $attr = array(
            "id" => "passwd",
            "class" => "passwd",
            "size" => 30,
        );
        return $this->_zv->formPassword($name, $default, $attr);
    }
    public function textPasswdNew()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("passwd_new");
        $name = "passwd_new";
        $attr = array(
            "id" => "passwd_new",
            "class" => "passwd_new",
            "size" => 30,
        );
        return $this->_zv->formPassword($name, $default, $attr);
    }
    public function textPasswdConfirm()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("passwd_confirm");
        $name = "passwd_confirm";
        $attr = array(
            "id" => "passwd_confirm",
            "class" => "passwd_confirm",
            "size" => 30,
        );
        return $this->_zv->formPassword($name, $default, $attr);
    }
    public function hiddenName()
    {
        $req->$this->_controller->getRequest();
        $default = $req->getParam("name");
        $name = "name";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenLogin()
    {
        $req->$this->_controller->getRequest();
        $default = $req->getParam("login");
        $name = "login";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenEmail()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("email");
        $name = "email";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenPasswd()
    {
        $req->$this->_controller->getRequest();
        $default = $req->getParam("passwd");
        $name = "passwd";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenPasswdNew()
    {
        $req->$this->_controller->getRequest();
        $default = $req->getParam("passwd_new");
        $name = "passwd_new";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenPasswdConfirm()
    {
        $req->$this->_controller->getRequest();
        $default = $req->getParam("passwd_confirm");
        $name = "passwd_confirm";
        return $this->_zv->formHidden($name, $default);
    }
}
