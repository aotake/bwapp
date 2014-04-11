<?php

$ModName = str_replace("Custom", "", basename(dirname(dirname(dirname(__FILE__)))));
require_once dirname(dirname(dirname(dirname(dirname(__FILE__)))))."/base/<{$ModuleName}>Base/Form/Validator/<{$TableName}>.php";

eval('
class '.$ModName.'Custom_Form_Validator_<{$TableName}> extends <{$ModuleName}>Base_Form_Validator_<{$TableName}>
{
}
');
