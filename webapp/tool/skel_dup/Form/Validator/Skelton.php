<?php

$BaseModName = "<{$ModuleName}>";
$TableName = basename(__FILE__, ".php");
$ModName = basename(dirname(dirname(dirname(__FILE__))));

$custom_file = dirname(dirname(dirname(dirname(dirname(__FILE__)))))."/custom/".$ModName."Custom/Form/Validator/".$TableName.".php";
$base_file = dirname(dirname(dirname(dirname(dirname(__FILE__)))))."/base/".$BaseModName."Base/Form/Validator/".$TableName.".php";

if(file_exists($custom_file)){
    require_once $custom_file;
    $parentClass = $ModName."Custom_Form_Validator_".$TableName;
} else {
    require_once $base_file;
    $parentClass = $BaseModName."Base_Form_Validator_".$TableName;
}

eval('
class '.$ModName.'_Form_Validator_'.$TableName.' extends '.$parentClass.'
{
}
');
