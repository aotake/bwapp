<?php
/**
 * <{$ModuleName}>_Model_<{$tableName}> モデル
 *
 * @category Model
 * @package Model_<{$ModuleName}>_Module_Bmathlog    
 */
$name = basename(__FILE__,".php");
$modname = basename(dirname(dirname(__FILE__)));

eval('

class '.$modname.'_Model_'.$name.' extends Ao_Model_Abstract
{
    public function del($id)
    {
        $select = $this->select();
        $select->where("id = ?", $id);
        $vos = $this->fetchAll($select);
        $vo = current($vos);
        $vo->set("modified", date("Y-m-d H:i:s"));
        $vo->set("delete_flag", 1);
        $this->save($vo);
    }
}

');

