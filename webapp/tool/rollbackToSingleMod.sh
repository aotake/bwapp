#!/bin/sh
#
# rollbackToSingleMod.sh
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

# 複製可能対応したモジュールをシングルに戻す
# モジュールのトップディレクトリで実行する

PWD=`pwd`
dname=`dirname $PWD`
chkdir=`basename $dname`
if [ "$chkdir" != "modules" ]; then
    echo "ERROR: execute this script at module top directory."
    echo "cd ../modules/mymod; sh ../../tool/rollbackToSingleMod.sh"
    exit
fi
. ../../tool/func.sh
modname=`basename $PWD`
toPascal $modname; ModName=$RESULT

pushd controllers
if [ -d Custom ]; then
    rm -fr Custom/
fi
if [ -d Base ]; then
    # カレントの Controller ファイルを削除
    rm *.php
    # Base のファイルをまるごと移動
    mv Base/* .
    # Baseディレクトリは削除
    rmdir Base
fi

# BaseController ファイルを変換
for f in `ls *BaseController.php`; do
    echo "====> $f"
    fname=`echo $f | sed "s/BaseController/Controller/"`
    # ファイル内に BaseController の文字列があれば変換
    grep "BaseController" $f
    if [ $? = 0 ]; then
        cat $f | sed "s/BaseController/Controller/" > tmp
        echo "---------> CONVERTED: $f => tmp"
        mv tmp $fname
        echo "---------> rename : tmp => $fname"
    else
        echo "---------> not converted"
    fi

    echo "---------> remove : $fname"
    rm -f $f
done
popd

# lib を戻す
pushd libs
if [ -d custom ]; then
    rm -fr custom
fi
if [ -d default ]; then
    rm -fr default
fi
if [ -d base ]; then
    mv base/${ModName}Base ${ModName}
    rmdir base
fi

files=`find ./${ModName} -type f -name "*.php" -print`
for f in $files; do
    echo "====>$f"

    STR="basename(dirname(dirname(dirname(dirname(dirname(__FILE__))))))"
    REP="basename(dirname(dirname(dirname(dirname(__FILE__)))))"

    cat $f | sed "s/${ModName}Base/${ModName}/g" |\
    sed "s/$STR/$REP/" > tmp
    mv tmp $f
done

