#!/bin/sh
#
# generate module files by recipe
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
# レシピファイルを読み込んで一括スクリプト作成をする
#
# レシピの書式：
#     テーブル名 モジュール名 ターゲット
#
# ターゲットについて：
#       指定無し、もしくは all: 全てのスクリプトを生成
#       指定在り：指定したもののみ生成
#
#
# ターゲット
#       vo,model,form,validator,manager,controller,view
#
#---サンプル
# category tabi all
# image tabi vo,model,form,validator,manager
# contents tabi all
#---/サンプル

ECHO=/bin/echo

RECIPE=$1

if [ ! -f "$RECIPE" ];then
    echo "Usage: $0 <RECIPE FILE>"
    exit
fi

CMD=

cat $RECIPE | grep -v "^#" | while read line
do
    $ECHO "======> RECIPE $line"
done

$ECHO -n "Do you want to continue? [y/n] "
read ans
if [ "$ans" != "y" ];then
    $ECHO "abort...."
    exit
fi

cat $RECIPE | grep -v "^#" | while read line
do
    $ECHO "======> [cooking] /bin/sh ./skel.sh $line"
    /bin/sh ./skel.sh $line
    $ECHO
    $ECHO "------> cooking result = $?"
    $ECHO
done
