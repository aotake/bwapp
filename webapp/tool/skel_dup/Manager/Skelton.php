<?php

# モジュール名ベース(オリジナルモジュールディレクトリ名の PascalCase)
$BaseModName = "<{$ModuleName}>";
$ManagerName = basename(__FILE__, ".php");
$ModName = basename(dirname(dirname(__FILE__)));

$custom_file = dirname(dirname(dirname(dirname(__FILE__))))."/custom/".$ModName."Custom/Manager/".$ManagerName.".php";
$base_file = dirname(dirname(dirname(dirname(__FILE__))))."/base/".$BaseModName."Base/Manager/".$ManagerName.".php";

if(file_exists($custom_file)){
    require_once $custom_file;
    $parentClass = $ModName."Custom_Manager_".$ManagerName;
} else {
    require_once $base_file;
    $parentClass = $BaseModName."Base_Manager_".$ManagerName;
}

eval('
class '.$ModName.'_Manager_'.$ManagerName.' extends '.$parentClass.'
{
}
');
