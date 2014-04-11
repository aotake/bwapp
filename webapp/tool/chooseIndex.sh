#!/bin/sh
#
# choose module Index controller script
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

##
## IndexController, AdminController とするコントローラを指定し
## ファイル名およびテンプレートディレクトリ名を変更する
##

# テーブル名
origTbl=$1
# モジュール名
origMod=$2

#if [ "$1" = "" ]; then
if [ $# -lt 2 ]; then
    /bin/echo "Usage: skel.sh <table_name> <module_name>"
    /bin/echo "    e.g.) skel.sh user_profile photo"
    exit
fi

. func.sh
toPascal $origTbl; pascalTbl=$RESULT
toCamel $origTbl; camelTbl=$RESULT
toPascal $origMod HYPHEN; pascalMod=$RESULT
toCamel $origMod; camelMod=$RESULT

## ディレクトリを設定
CONTROLLER_DIR=../modules/$origMod/controllers
VIEW_DIR_TOP=../modules/$origMod/templates

## IndexController
SRC=$CONTROLLER_DIR/${pascalTbl}Controller.php
DST=$CONTROLLER_DIR/IndexController.php
if [ -f $SRC ]; then
    SRC_CLASS=${pascalMod}_${pascalTbl}Controller
    DST_CLASS=${pascalMod}_IndexController
    cat $SRC | sed "s/$SRC_CLASS/$DST_CLASS/" > $DST
    echo "---->convert $SRC"
    echo "---->done: $DST"
    rm -f $SRC
else
    echo "---->no convert $SRC"
fi
## AdminController
SRC=$CONTROLLER_DIR/${pascalTbl}AdminController.php
DST=$CONTROLLER_DIR/AdminController.php
if [ -f $SRC ]; then
    SRC_CLASS=${pascalMod}_${pascalTbl}AdminController
    DST_CLASS=${pascalMod}_AdminController
    cat $SRC | sed "s/$SRC_CLASS/$DST_CLASS/" > $DST
    echo "---->convert $SRC"
    echo "---->done: $DST"
    rm -f $SRC
else
    echo "---->no convert $SRC"
fi
## index templates
SRC=$VIEW_DIR_TOP/$origTbl
DST=$VIEW_DIR_TOP/index
if [ -d $SRC ];then
    mv $SRC $DST
    echo "---->rename $SRC"
    echo "---->to: $DST"
else
    echo "---->not rename $SRC"
fi
## admin templates
SRC=$VIEW_DIR_TOP/${origTbl}-admin
DST=$VIEW_DIR_TOP/admin
if [ -d $SRC ];then
    mv $SRC $DST
    echo "---->rename $SRC"
    echo "---->to: $DST"
else
    echo "---->not rename $SRC"
fi

/bin/echo
/bin/echo done.

