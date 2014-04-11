<?php 
/**
 * UserRole Manager
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

class Manager_UserRole extends Manager
{
    var $_controller;
    var $sAuth;
    
    public function __construct(&$controller)
    {
        $this->_controller = $controller;
        $this->sAuth = new Zend_Session_Namespace("auth");;
    }

    /**
     * getBelognUserByRid
     */
    public function getBelongUsers($rid)
    {
        $model = new Model_UserRole();
        $modelU = new Model_User();
        $modelR = new Model_Role();
        $select = $model->select();
        $select
            ->setIntegrityCheck(false)
            ->from(array("ur" => $model->name(), array("")))
            ->joinLeft(
                array("r" => $modelR->name()),
                "ur.rid = r.rid",
                array("*"))
            ->joinLeft(
                array("u" => $modelU->name()),
                "ur.uid = u.uid",
                array("*"))
            ->where("ur.rid = ?", $rid);
        $res = $model->fetchAll($select, true);
        $vos = null;
        if($res){
            $vos = $this->_assignCommonVos($res);
        }
        return $vos;
    }
    /**
     * getUsersExcept
     *
     * 引数のユーザ以外のユーザ
     *
     * @param Array of UserVo
     * @return Array of CommonVo
     */
    public function getUsersExcept($users)
    {
        $uids = array();
        foreach($users as $item){
            if(is_numeric($item)){
                $uids[] = (int)$item;
            } else if(is_array($item)){
                $uids[] = (int)$item["uid"];
            } else if($item instanceof Ao_Vo_Abstract){
                $uids[] = (int)$item->get("uid");
            } else{
                throw new Zend_Exception("引数が不正です");
            }
        }
        $model = new Model_User();
        $select = $model->select();
        if(is_array($uids)){
            $select->where("uid not in (?)", $uids);
        }
        //print $select->__toString();exit;
        return $model->fetchAll($select);
    }

    public function add($uid, $rids)
    {
        $model = new Model_UserRole();
        foreach($rids as $rid){
            $vo = $model->getVo();
            $vo->set("uid", $uid);
            $vo->set("rid", $rid);
            $model->save($vo);
            unset($vo);
        }
    }
    public function remove($uid, $rids)
    {
        $model = new Model_UserRole();
        foreach($rids as $rid){
            $select = $model->select();
            $select->where("rid = ?", (int)$rid);
            $select->where("uid = ?", (int)$uid);
            $vos = $model->fetchAll($select);
            $vo = current($vos);
            $urid = $vo->get("urid");
            $model->del($urid);
        }
    }
}
