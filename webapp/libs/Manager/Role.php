<?php 
/**
 * Role Manager
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

class Manager_Role extends Manager
{
    const ROLE_ID_ADMIN = 1;
    const ROLE_ID_MEMBER = 2;
    const ROLE_ID_CELEB = 4;
    var $_controller;
    var $sAuth;
    
    public function __construct(&$controller)
    {
        $this->_controller = $controller;
        $this->sAuth = new Zend_Session_Namespace("auth");;
    }

    /**
     * getRole
     *
     * 指定 rid または実行ユーザの rid の Vo を返す
     * 実行ユーザの rid は、当該ユーザの最高権限の rid になっている
     *
     * @param $id ユーザID
     * @return array of Roledata(Vo ではない)
     */
    public function getRole($id = null)
    {
        if($id == null && $this->sAuth->role_id){
            $id = $this->sAuth->role_id;
        }

        if(!$id){
            return false;
        }

        $model = new Model_Role();
        $select = $model->select();
        $select->where("rid = ?", $id);
        $res = $model->fetchAll($select);
        if($res){
            return current($res);
        } else {
            return null;
        }
    }
    /**
     * gets
     *
     * ロール Vo の配列を返す
     *
     * @param $limit 取得件数
     * @param $offset 取得開始位置
     * @return Array of UsreVo
     */
    public function gets()
    {
        $model = new Model_Role();
        $select = $model->select();
        $select->order("rid desc");
        return $model->fetchAll($select);
    }

    /**
     * getVo
     *
     */
    public function getVo($_params = null)
    {
        $model = new Model_Role();
        return $model->getVo($_params);
    }

    public function save($vo)
    {
        $model = new Model_Role();
        return $model->save($vo);
    }
    public function update($vo)
    {
        $rid_orig = $this->_controller->getRequest()->getParam("rid_orig");
        $model = new Model_Role();
        $where = $model->getAdapter()->quoteInto("rid = ?", (int)$rid_orig);
        $model->save($vo, $where);
    }

    public function delete($rid)
    {
        $model = new Model_Role();
        $model->del($rid);
    }

    /**
     * getRolesByUid
     *
     * ロールIDが若い順（権限がある順）にソートしてロールリストを取得
     *
     * @param $rid ユーザID
     * @return Array of CommonVo 
     */
/*
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
