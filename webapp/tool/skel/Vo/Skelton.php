<?php
/**
 * <{$ModuleName}>_Vo_<{$tableName}> Vo
 *
 * @category Vo
 * @package Vo_<{$ModuleName}>_Module_Bmathlog
 */
$name = basename(__FILE__,".php");
$modname = basename(dirname(dirname(__FILE__)));

eval('
class '.$modname.'_Vo_'.$name.' extends Ao_Vo_Abstract {
}
');

