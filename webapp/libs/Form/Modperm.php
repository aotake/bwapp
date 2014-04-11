<?php
/**
 * Modperm Form
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

class Form_Modperm
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
        $validator = new Form_Validator_Modperm($this->_controller);
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
            "id" => $this->hiddenId(),
            "uid" => $this->hiddenUid(),
            "dirname" => $this->textDirname(),
            "permission" => $this->textPermission(),
            "note" => $this->tareaNote(),
            "created" => $this->textCreated(),
            "modified" => $this->textModified(),
            "delete_flag" => $this->textDeleteFlag(),
        );
    }
    public function textId()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("id");
        $name = "id";
        $attr = array(
            "id" => "id",
            "class" => "id",
            "size" => 2,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function textUid()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("uid");
        $name = "uid";
        $attr = array(
            "id" => "uid",
            "class" => "uid",
            "size" => 2,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function textDirname()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("dirname");
        $name = "dirname";
        $attr = array(
            "id" => "dirname",
            "class" => "dirname",
            "size" => 30,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function textPermission()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("permission");
        $name = "permission";
        $attr = array(
            "id" => "permission",
            "class" => "permission",
            "size" => 2,
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
            "class" => "note ckeditor",
            "cols" => 60,
            "rows" => 5,
        );
        return $this->_zv->formTextarea($name, $default, $attr);
    }
    public function textCreated()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("created");
        $name = "created";
        $attr = array(
            "id" => "created",
            "class" => "created",
            "size" => 30,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function textModified()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("modified");
        $name = "modified";
        $attr = array(
            "id" => "modified",
            "class" => "modified",
            "size" => 30,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function textDeleteFlag()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("delete_flag");
        $name = "delete_flag";
        $attr = array(
            "id" => "delete_flag",
            "class" => "delete_flag",
            "size" => 2,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function confirmElements()
    {
        return array(
            "id" => $this->hiddenId(),
            "uid" => $this->hiddenUid(),
            "dirname" => $this->hiddenDirname(),
            "permission" => $this->hiddenPermission(),
            "note" => $this->hiddenNote(),
            "created" => $this->hiddenCreated(),
            "modified" => $this->hiddenModified(),
            "delete_flag" => $this->hiddenDeleteFlag(),
        );
    }
    public function hiddenId()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("id");
        $name = "id";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenUid()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("uid");
        if(!$default){
            $sess = new Zend_Session_Namespace("auth");
            $default = $sess->user_id;
        }
        $name = "uid";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenDirname()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("dirname");
        $name = "dirname";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenPermission()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("permission");
        $name = "permission";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenNote()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("note");
        $name = "note";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenCreated()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("created");
        $name = "created";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenModified()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("modified");
        $name = "modified";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenDeleteFlag()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("delete_flag");
        $name = "delete_flag";
        return $this->_zv->formHidden($name, $default);
    }

    public function showElements()
    {
        return array(
            "id" => $this->showId(),
            "uid" => $this->showUid(),
            "dirname" => $this->showDirname(),
            "permission" => $this->showPermission(),
            "note" => $this->showNote(),
            "created" => $this->showCreated(),
            "modified" => $this->showModified(),
            "delete_flag" => $this->showDeleteFlag(),
        );
    }
    public function showId()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("id");


        return $default;
    }
    public function showUid()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("uid");


        return $default;
    }
    public function showDirname()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("dirname");


        return $default;
    }
    public function showPermission()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("permission");


        return $default;
    }
    public function showNote()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("note");


        return $default;
    }
    public function showCreated()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("created");


        return $default;
    }
    public function showModified()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("modified");


        return $default;
    }
    public function showDeleteFlag()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("delete_flag");


        return $default;
    }

}
