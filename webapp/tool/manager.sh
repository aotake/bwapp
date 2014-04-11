#!/bin/sh
#
# make manager files
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
## Form, Validator, Model, Vo を生成するスクリプト
##
##

# 生成するマネージャのモジュール名、マネージャ名を指定する
module_name=$1
manager_name=$2
# マネージャで利用するモジュールを以下の書式で指定
#    mod_name=tbl_name:mod_name2=tbl_name2:....
use_module=$4


#if [ "$1" = "" ]; then
if [ $# -lt 4 ]; then
    /bin/echo "Usage: $0 <module_name> <manager_name> Manager mod1=tbl1:mod2=tbl2:..."
    /bin/echo "    e.g.) $0 admin-photo index Manager user=user_prof:user=user_prof_opt"
    exit
fi

. ./func.sh

toPascal $module_name HYPHEN; MODULE_NAME=$RESULT
toPascal $manager_name; MANAGER_NAME=$RESULT

## ディレクトリを設定
LIBDIR=../modules/$module_name/libs/$MODULE_NAME
HANDLER_DIR=$LIBDIR/Handler
MANAGER_DIR=$LIBDIR/Manager
FORM_DIR=$LIBDIR/Form
VALIDATOR_DIR=$LIBDIR/Form/Validator
MODEL_DIR=$LIBDIR/Model
VO_DIR=$LIBDIR/Vo
if [ ! -d $LIBDIR ]; then
    /bin/echo "ERROR: No such libdir: $LIBDIR"
    /bin/echo "----> Please check LIBDIR path"
    exit
fi

_DIRS="$HANDLER_DIR $MANAGER_DIR $FORM_DIR $VALIDATOR_DIR $MODEL_DIR $VO_DIR"
for d in $_DIRS; do
    if [ ! -d $d ]; then
        mkdir $d
    fi
done

## skelton から文字列置換してコピー
SKEL_DIR=./skel

## Manager ファイルを生成してコピー
/bin/echo -n "===> put ${MANAGER_DIR}/${MANAGER_NAME}.php"
php ./dbconf.php $module_name $manager_name Manager $use_module > tmp
CMD="cp tmp $MANAGER_DIR/${MANAGER_NAME}.php"
if [ -f $MANAGER_DIR/${MANAGER_NAME}.php ]; then
    /bin/echo 
    /bin/echo "already exist: $MANAGER_DIR/${MANAGER_NAME}.php"
    /bin/echo -n "Do you want to replace this file? [y/N]"
    read answer
    if [ "$answer" = "y" ]; then
        ${CMD}
        /bin/echo ".....replaced."
        /bin/echo
    else
        /bin/echo ".....skip."
        /bin/echo
    fi
else
    ${CMD}
    /bin/echo ".....created."
fi

## ゴミ清掃
rm -f ./tmp
/bin/echo
/bin/echo done.

