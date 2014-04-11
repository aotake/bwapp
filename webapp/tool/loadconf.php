<?php
/**
 * load webapp/config/config.ini for bash
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
#
# 引数がなければ .htaccess の APPLICATION_ENV を参照して設定を取り出す
# 引数があれば指定された環境の設定を取り出す
#
# Shell 側では eval `php loadconf.php` して環境値を参照する
# 
require_once "CodeTool.php";

# デフォルト設定ファイル
$file = "../config/config.ini";
# カスタム設定ファイルがあればそちらを見る
if(file_exists("../config/custom/config.ini")){
    $file = "../config/custom/config.ini";
}

$tool = new CodeTool();
$tool->setupEnv();
if (isset($argv[1]) && in_array($argv[1], array("production", "development", "debug"))){
    $section = $argv[1];
} else {
    $section = $tool->getApplicationEnv();
}
$tool->loadConfig($file, $section);
$conf = $tool->getConfig();
$dsn = $conf->db->dsn->toArray();
$prefix = $conf->db->prefix;
$charset = $conf->db->charset;
print "APPLICATION_ENV=$section\n";
print "PREFIX=$prefix\n";
print "CHARSET=$charset\n";
print "DBUSER={$dsn["username"]}\n";
