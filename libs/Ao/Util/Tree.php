<?php
/**
 * Tree Structure Utility
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
 * @package       Ao.Util
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * ツリー構造のデータ表示管理
 */
class Ao_Util_Tree
{
    private $model;
    private $parent_key; // 通常 "id"
    private $parent_ref; // 通常 "parent_id" とか "pid"
    private $label; // たとえば "title" とか "name" というカラム
    private $wheres;
    private $orders;

    public function __construct($model_name, $pkey, $pref, $label)
    {
        $this->model = $model_name;
        $this->parent_key = $pkey;
        $this->parent_ref = $pref;
        $this->label = $label;
        $this->wheres = array();
        $this->orders = array();
    }

    public function addWhere($where)
    {
        $this->wheres[] = $where;
        return $this;
    }

    public function addOrder($order)
    {
        $this->orders[] = $order;
        return $this;
    }

    /**
     * getSelectArray()
     *
     * ツリー構造を反映して配列を取得(select メニュー用）
     */
    public function getSelectArray($parent_id = 0, $arr = array(), $depth = -1)
    {
        $items = array();
        $model = new $this->model();
        $select = $model->select()
            ->where($this->parent_ref." = ".$parent_id);
        if($this->wheres){
            foreach($this->wheres as $item){
                $select->where($item);
            }
        }
        if($this->orders){
            foreach($this->orders as $item){
                $select->order($item);
            }
        }
        $vos = $model->fetchAll($select);
        if(!$vos){
            return $arr;
        }
        foreach($vos as $i => $vo){
            $d = $depth + 1;
            $vo->set("depth", $d);
            $header = str_repeat("--", $d);
            $a["key"] = $vo->get($this->parent_key);
            $a["val"] = $header." ".$vo->get($this->label);
            $a["depth"] = $d;
            $a["name"] = $vo->get($this->label);
            array_push($arr, $a);
            unset($a);
            // 子供があれば子供を追加(再帰的に末端の子供まで取得）
            $arr = $this->getSelectArray($vo->get($this->parent_key), $arr, $d);
        }
        return $arr;
    }
    /**
     * getChildArray();
     *
     * 指定 parent_id の子供の一覧(孫以下は取得しない）
     */
    public function getChildren($parent_id = 0)
    {
        if($parent_id !== 0 && $parent_id == ""){
            $parent_id = 0;
        }
        $items = array();
        $model = new $this->model();
        $select = $model->select()
            ->where($this->parent_ref." = ".$parent_id);
        if($this->wheres){
            foreach($this->wheres as $item){
                $select->where($item);
            }
        }
        if($this->orders){
            foreach($this->orders as $item){
                $select->order($item);
            }
        }
        $vos = $model->fetchAll($select);
        if(!$vos){
            return array();
        }
        $arr = array();
        foreach($vos as $i => $vo){
            array_push($arr, $vo);
        }
        return $arr;
    }
    
    /**
     *
     * treePath()
     *
     * カレント id までの TOP からのパンくず
     */
    public function treePath($id = 0, $arr = array())
    {
        $model = new $this->model();
        $select = $model->select()
            ->where($this->parent_key." = ".$id);        
        $vos = $model->fetchAll($select);
        if(!$vos){
            return null;
        }
        $vo = current($vos);
        $this->arr[] = $vo;

        // 親のID があれば取得
        $parent_ref = $vo->get($this->parent_ref);
        if($parent_ref == 0 || $parent_ref == ""){
            return $this->arr;
        }
        $this->treePath($parent_ref);
        return $this->arr;
    }
}
