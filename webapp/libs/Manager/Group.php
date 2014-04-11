<?php
/**
 * Group Manager
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

class Manager_Group extends Manager
{
    var $controller;
    var $request;

    public function __construct(&$controller)
    {
        $this->controller = $controller;
        $this->request = $this->controller->getRequest();
        $this->auth = new Zend_Session_Namespace("auth");
        $this->mGroup= new Model_Group();
    }

    public function getGroupVo($param = array())
    {
        $model =& $this->mGroup;
        $vo = $model->getVo($param);
        //$vo->set("is_released", 1);
        return $vo;
    }
    public function getGroupByParentId($parent_id = 0, $with_children = false)
    {
        $tree = new Ao_Util_Tree("Model_Group", "id", "parent_id", "name");
        $tree->addWhere("delete_flag = 0 or delete_flag is null")
            ->addOrder("id asc");
        $vos = $tree->getChildren($parent_id);
        if($with_children){
            foreach($vos as $i =>$vo){
                $children = $this->getGroupByParentId($vo->get("id"));
                $vo->set("children", $children);
                $vos[$i] = $vo;
            }
        }
        return $vos;
    }
    public function getGroupSelectArray()
    {
        $tree = new Ao_Util_Tree("Model_Group", "id", "parent_id", "name");
        $tree->addWhere("delete_flag = 0 or delete_flag is null")
            ->addOrder("id asc");
        return $tree->getSelectArray();
    }
    public function getPath($current_id)
    {
        $tree = new Ao_Util_Tree("Model_Group", "id", "parent_id", "name");
        $tree->addWhere("delete_flag = 0 or delete_flag is null");
        $path = $tree->treePath($current_id);
        if(isset($path[0])){
            $path[0]->set("is_current",1);
        }
        if(is_array($path)){
            $path = array_reverse($path);
        }
        return $path;
    }
    public function getGroup($id = null)
    {
        $model =& $this->mGroup;
        $select = $model->select();
        $select->where("delete_flag = 0 or delete_flag is null");
        $select->order("id asc");
        if($id){
            $select->where("id = ?", $id);
        }
        return $model->fetchAll($select);
    }
    public function saveGroup($vo)
    {
        $model =& $this->mGroup;
        $model->getAdapter()->beginTransaction();
        try{
            $model->save($vo);
            $model->getAdapter()->commit(); 
        } catch(Exception $e){
            $model->getAdapter()->rollback(); 
            print $e->getMessage();
            throw $e;
        }
    }
    public function deleteGroup($id = null)
    {
        $req = $this->controller->getRequest();
        $id = $req->getParam("id");
        if($id == ""){
            throw new Zend_Exception("ID が取得できませんでした");
        }
        return $this->mGroup->del($id);
    }

/* sample of left join
    public function getRolesByUid($rid = null)
    {
        $model = new Model_Role();
        $model_ur= new Model_RoleRole();
        $select = $model->select();
        $select->setIntegrityCheck(false)
            ->from(array("r" => $model->name()), array("*"))
            ->joinLeft(
                array("ur" => $model_ur->name()),
                "r.rid = ur.rid",
                array("*"))
            ->where("ur.rid = ?", (int)$rid)
            ->order("r.rid asc")
            ;
        $res = $model->fetchAll($select, true);
        if($res){
            return $this->_assignCommonVos($res);
        } else {
            return null;
        }
    }
*/

}
