<?php

$ModName = str_replace("Custom", "", basename(dirname(dirname(__FILE__))));
require_once dirname(dirname(dirname(dirname(__FILE__))))."/base/<{$ModuleName}>Base/Manager/<{$ManagerName}>.php";

eval('
class '.$ModName.'Custom_Manager_<{$ManagerName}> extends <{$ModuleName}>Base_Manager_<{$ManagerName}>
{
}
');
