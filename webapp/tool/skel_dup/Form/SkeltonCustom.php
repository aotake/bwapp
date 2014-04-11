<?php

$ModName = str_replace("Custom", "", basename(dirname(dirname(__FILE__))));
require_once dirname(dirname(dirname(dirname(__FILE__))))."/base/<{$ModuleName}>Base/Form/<{$TableName}>.php";

eval('
class '.$ModName.'Custom_Form_<{$TableName}> extends <{$ModuleName}>Base_Form_<{$TableName}>
{
}
');
