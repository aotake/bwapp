#!/usr/bin/php
<?php
/**
 * show owa database setting
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
 * @package       Ao.webapp.tool
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author        aotake (aotake@bmath.org)
 */

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
echo "[DB Configuration]\n";
echo "Type: ".$conf->db->type."\n";
echo "Host: ".$dsn["host"]."\n";
echo "Name: ".$dsn["dbname"]."\n";
echo "User: ".$dsn["username"]."\n";
echo "Pass: ".$dsn["password"]."\n";
echo "\n";
echo "Create database unless you did it.\n";
if($conf->db->type == "mysql"){
    echo "----> create database ".$dsn["dbname"]
        ." default character set ".$conf->db->charset.";\n";
    echo "----> grant all privileges on ".$dsn["dbname"].".* to ".$dsn["username"]."@".$dsn["host"]." identified by \"".$dsn["password"]."\";";                                                                                                                     
} else if($conf->db->type == "pgsql"){
    echo "----> /usr/local/pgsql/bin/createuser --createdb --no-adduser --pwprompt ".$dsn["username"]."\n";
    echo "--------> password is ".$dsn["password"]."\n";
    echo "----> /usr/local/pgsql/bin/createdb -U ".$dsn["username"]." -E ".$conf->db->charset." ".$dsn["dbname"]."\n";
}

echo "\n";
