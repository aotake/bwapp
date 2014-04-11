<?php
/**
 * Group Form
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

class Form_Group
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
        $validator = new Form_Validator_Group($this->_controller);
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
            "parent_id" => $this->selectParentId(),
            "name" => $this->textName(),
            "note" => $this->tareaNote(),
            "depth" => $this->hiddenDepth(),
            "sort" => $this->textSort(),
            "created" => $this->hiddenCreated(),
            "modified" => $this->hiddenModified(),
            "delete_flag" => $this->hiddenDeleteFlag(),
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
            "size" => 30,
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
            "size" => 30,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function selectParentId()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("parent_id");
        $name = "parent_id";
        $attr = array(
            "id" => "parent_id",
            "class" => "parent_id",
        );
        // ツリー構造を反映した select メニューを作る
        $tree = new Ao_Util_Tree("Model_Group", "id", "parent_id", "name");
        $tree->addWhere("delete_flag = 0 or delete_flag is null");
        $_array = $tree->getSelectArray();
        $array[null] = "---選択して下さい---";
        foreach($_array as $item){
            $array[$item["key"]] = $item["val"];
        }
//print_r($array);exit;
/*        $model = new Model_Group();
        $select = $model->select()
            ->where("delete_flag = 0 or delete_flag is null")
            ;
        $vos = $model->fetchAll($select);
        $array = array();
        $array["*"] = "---選択して下さい---";
        if($vos){
            foreach($vos as $vo){
                $array[$vo->get("id")] = $vo->get("name");
            }
        }
*/
        return $this->_zv->formSelect($name, $default, $attr, $array);
        
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
        return $this->_zv->formText($name, $default, $attr);
    }
    public function tareaNote()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("note");
        $name = "note";
        $attr = array(
            "id" => "note",
            "class" => "note",
            "cols" => 60,
            "rows" => 5,
        );
        return $this->_zv->formTextarea($name, $default, $attr);
    }
    public function textDepth()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("depth");
        $name = "depth";
        $attr = array(
            "id" => "depth",
            "class" => "depth",
            "size" => 2,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function textSort()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("sort");
        $name = "sort";
        $attr = array(
            "id" => "sort",
            "class" => "sort",
            "size" => 2,
        );
        return $this->_zv->formText($name, $default, $attr);
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
            "size" => 30,
        );
        return $this->_zv->formText($name, $default, $attr);
    }
    public function confirmElements()
    {
        return array(
            "id" => $this->hiddenId(),
            "uid" => $this->hiddenUid(),
            "parent_id" => $this->hiddenParentId(),
            "name" => $this->hiddenName(),
            "note" => $this->hiddenNote(),
            "depth" => $this->hiddenDepth(),
            "sort" => $this->hiddenSort(),
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
    public function hiddenParentId()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("parent_id");
        $name = "parent_id";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenName()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("name");
        $name = "name";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenNote()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("note");
        $name = "note";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenDepth()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("depth");
        $name = "depth";
        return $this->_zv->formHidden($name, $default);
    }
    public function hiddenSort()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("sort");
        $name = "sort";
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

}
