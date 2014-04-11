#!/bin/sh
#
# generate code by skelton
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
## [NOTE] Manager は manager.sh で生成する
##

# テーブル名
origTbl=$1
# モジュール名
origMod=$2
# 作成対象(form,validator,model,vo,manager,controller,view)
target=$3
# 作成対象が controller の場合
isAdmin=$4

# for GIT commit file
GENERATEFILES=gitcommit.txt
cp /dev/null $GENERATEFILES

if [ ! -d "./.cache/" ]; then
    mkdir ./.cache
    if [ $? != 0 ]; then
        echo "Error: not found cache dir"
        echo "    --> please create .cache directory"
        exit
    fi
fi
if [ ! -d "./.templates_c/" ]; then
    mkdir ./.templates_c
    if [ $? != 0 ]; then
        echo "Error: not found compile dir"
        echo "    --> please create .templates_c directory"
        exit
    fi
fi

if [ "$target" = "" ]; then
    target=all
fi

#if [ "$1" = "" ]; then
if [ $# -lt 2 ]; then
    /bin/echo "Usage: skel.sh <table_name> <module_name> [target]"
    /bin/echo "    target: vo,model,form,validator,controller"
    /bin/echo "    e.g.) skel.sh user_profile photo"
    exit
fi

. ./func.sh
toPascal $origTbl; pascalTbl=$RESULT
toCamel $origTbl; camelTbl=$RESULT
toPascal $origMod HYPHEN; pascalMod=$RESULT
toCamel $origMod; camelMod=$RESULT

#echo origTbl=$origTbl
#echo origMod=$origMod
#echo pascalTbl=$pascalTbl
#echo camelTbl=$camelTbl
#echo pascalMod=$pascalMod
#echo camelMod=$camelMod
#exit

## ディレクトリを設定
LIBDIR=../modules/$origMod/libs/$pascalMod
HANDLER_DIR=$LIBDIR/Handler
CONTROLLER_DIR=../modules/$origMod/controllers
VIEW_DIR_TOP=../modules/$origMod/templates
MANAGER_DIR=$LIBDIR/Manager
FORM_DIR=$LIBDIR/Form
VALIDATOR_DIR=$LIBDIR/Form/Validator
MODEL_DIR=$LIBDIR/Model
VO_DIR=$LIBDIR/Vo

. ./skel_dircheck.sh

## skelton から文字列置換してコピー
SKEL_DIR=./skel

if [ "$target" = "all" ]; then
    . ./skel_model.sh
    . ./skel_vo.sh
    . ./skel_form.sh
    . ./skel_validator.sh
    . ./skel_controller.sh
    . ./skel_view.sh
    . ./skel_manager.sh
else
    _target=`echo $target | sed -e 's/,/ /g'`
    for t in $_target; do
        # target変数を再定義
        target=$t
        # 生成スクリプトを実行
        . ./skel_${target}.sh
    done
fi

# git add 
for f in `cat $GENERATEFILES`; do
    echo git add $f | sed "s/\.\.\/modules\/${origMod}\///"
done

## ゴミ清掃
rm -f tmp
/bin/echo
/bin/echo done.

