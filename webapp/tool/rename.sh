#!/bin/sh
#
# rename.sh
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
# git リポジトリにあるディレクトリ名と変える場合に
# ディレクトリ名、ファイルの中の文字列等を一括変換するスクリプト
# duplicatable なモジュールではないので別モジュールとしてgitに登録し直す必要有り。
#
# 

LANG=C

. func.sh

# git リポジトリのディレクトリ名
FROM_MODNAME=$1
# ローカルに保存する時のディレクトリ名
TO_MODNAME=$2
# 変換対象のバックアップファイルを保存しない場合 "clean" を指定
OPTION=$3

if [ "$TO_MODNAME" = "" ];then
    echo "Usage $0 <FROM_MODNAME> <TO_MODNAME> [clean]"
    exit
fi

toPascal $FROM_MODNAME HYPHEN; FROM_PASCAL=$RESULT
toPascal $TO_MODNAME HYPHEN; TO_PASCAL=$RESULT

echo "$FROM_MODNAME => $TO_MODNAME"
echo "$FROM_PASCAL => $TO_PASCAL"

# snake case のディレクトリ名を変更
RENAME_TARGET1=`find ../modules/$TO_MODNAME -type d -name $FROM_MODNAME -print`
echo "---->rename1 filename/dirname target(Snake Case):"
for f in $RENAME_TARGET1; do
    echo "--------->  $f"
done

# pascal case のディレクトリ名を変更
RENAME_TARGET2=`find ../modules/$TO_MODNAME -type d -name $FROM_PASCAL -print`
echo "---->rename2 filename/dirname target(Pascal Case):"
for f in $RENAME_TARGET2; do
    echo "--------->  $f"
    DIRNAME=`dirname $f`
    mv $f $DIRNAME/$TO_PASCAL
done


# snake case のディレクトリ名を変更
REPLACE_TARGET1=`grep -r $FROM_MODNAME ../modules/$TO_MODNAME/* | awk 'BEGIN{FS=":"}{print $1}' | uniq | grep -v "*.bak$"`
echo "---->replace string from(snake case):"
for f in $REPLACE_TARGET1; do
    echo "--------->  $f"
    cat $f | sed "s/${FROM_MODNAME}/${TO_MODNAME}/g" > $f.new
    mv $f.new $f;
done

# pascal case の文字列を変更
REPLACE_TARGET2=`grep -r "${FROM_PASCAL}_" ../modules/$TO_MODNAME/* | awk 'BEGIN{FS=":"}{print $1}' | uniq | grep -v "*.bak$"`
echo "---->replace string from(Pascal Case):"
for f in $REPLACE_TARGET2; do
    echo "--------->  $f"
    cat $f | sed "s/${FROM_PASCAL}_/${TO_PASCAL}_/g" > $f.new
    mv $f.new $f;
done

