<?php
#
# make csv from meta.xml
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

#
# meta.xml の <table> ノードをコピペしたファイルを Excel で読める CSV ファイルに変換する
#
# 出力文字コードは SJIS-win
#
if(count($argv) < 2){
    print "Error: no csv file\n";
    print "Usage: php ".$argv[0]." <csvfile>\n\n";exit;
}
$lines = file($argv[1]);
foreach($lines as $line){
    if(preg_match("/^#\s*colname.+/", $line)){
        $line = str_replace("|", ",", $line);
        $line = str_replace("#", "", $line);
    }
    else if(preg_match("/^#.+/", $line)){
        continue;
    }
    $line = mb_convert_encoding($line, "SJIS-win", "UTF-8");
    $cols = explode(",", $line);
    $cols = array_map("trim", $cols);
    print implode(",", $cols)."\n";
}

