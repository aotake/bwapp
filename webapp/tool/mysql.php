<?php
#
# mysql console
#
# Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>
#
# @copyright     Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
# @link          http://bmath.jp Bmath Web Application Platform Project
# @package       Ao.webapp.tool
# @since
# @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
# @author        aotake (aotake@bmath.org)
#


require_once "CodeTool.php";

$conf_file = "../config/config.ini";
if(file_exists("../config/custom/config.ini")){
    $conf_file = "../config/custom/config.ini";
}

$tool = new CodeTool();
$section = $tool->getApplicationEnv();
$tool->setupEnv();
$tool->loadConfig($conf_file, $section);
$conf = $tool->getConfig();
$dsn = $conf->db->dsn->toArray();
$cmd = "mysql -h ".$dsn["host"]." -u ".$dsn["username"]." -p --password=".$dsn["password"]." ".$dsn["dbname"];
echo $cmd;
