<?php

$BaseModule = "<{$MOD_PASCAL}>";
$ModName = Ao_Util_Str::toPascal(basename(dirname(dirname(__FILE__))));
$CtrName = str_replace("Controller.php", "", basename(__FILE__));

$custom_file = dirname(__FILE__)."/Custom/".$CtrName."CustomController.php";
$base_file = dirname(__FILE__)."/Base/".$CtrName."BaseController.php";
if(file_exists($custom_file)){
    require_once $custom_file;
    $parentClass = $CtrName."CustomController";
} else {
    require_once $base_file;
    $parentClass = $CtrName."BaseController";
}

eval('
class '.$ModName.'_'.$CtrName.'Controller extends '.$BaseModule.'_'.$parentClass.'
{
}
');
