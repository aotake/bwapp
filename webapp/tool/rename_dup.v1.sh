#!/bin/sh
#
# rename_dup.v1.sh
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
#
# ただし duplicatable なモジュールのみ
#
# libs/custom がオリジナルモジュール名を使っている時代の複製モジュールのみ対応
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

if [ ! -d ../modules/$TO_MODNAME/controllers/Base ]; then
    echo "Error: this module is NOT duplicatable type module"
    exit
fi

if [ ! -d ../modules/$TO_MODNAME/libs/base ]; then
    echo "Error: this module is NOT duplicatable type module"
    exit
fi

toPascal $FROM_MODNAME HYPHEN; FROM_PASCAL=$RESULT
toPascal $TO_MODNAME HYPHEN; TO_PASCAL=$RESULT

echo "$FROM_MODNAME => $TO_MODNAME"
echo "$FROM_PASCAL => $TO_PASCAL"

# libs の ディレクトリ名を変更
if [ -d ../modules/$TO_MODNAME/libs/default/$FROM_PASCAL ]; then
    echo "mv ../modules/$TO_MODNAME/libs/default/$FROM_PASCAL ../modules/$TO_MODNAME/libs/default/$TO_PASCAL"
    mv ../modules/$TO_MODNAME/libs/default/$FROM_PASCAL ../modules/$TO_MODNAME/libs/default/$TO_PASCAL
fi
# 古い libs は削除
if [ -d ../modules/$TO_MODNAME/libs/$FROM_PASCAL ]; then
    echo "rm -fr ../modules/$TO_MODNAME/libs/$FROM_PASCAL"
    rm -fr ../modules/$TO_MODNAME/libs/$FROM_PASCAL
fi

# ini ファイルのモジュール名部分を変換(sample はそのまま)
CONF_DEFAULT=../modules/$FROM_PASCAL/config/config.ini
CONF_CUSTOM=../modules/$FROM_PASCAL/config/custom/config.ini
if [ -f $CONF_DEFAULT ]; then
    echo "convert default config.ini...."
    cat $CONF_DEFAULT | sed "s/^${TO_MODNAME}/${FROM_MODNAME}/" > tmp
    mv tmp > $CONF_DEFAULT
fi
if [ -f $CONF_CUSTOM ]; then
    echo "convert custom config.ini...."
    cat $CONF_CUSTOM | sed "s/^${TO_MODNAME}/${FROM_MODNAME}/" > tmp
    mv tmp > $CONF_CUSTOM
fi

# Controlelr 変換
# 実態となる Custom, Base の両コントローラはそのまま
CONTROLLERS=`grep -r "class ${FROM_PASCAL}_" ../modules/$TO_MODNAME/controllers/*.php | awk 'BEGIN{FS=":"}{print $1}' | uniq | grep -v "*.bak$"`
echo "---->replace string from(Pascal Case):"
for f in $CONTROLLERS; do
    echo "--------->  $f"
    cat $f | sed "s/class ${FROM_PASCAL}_/class ${TO_PASCAL}_/g" > $f.new
    mv $f.new $f;
done

exit

# snake case のデレクトリ名を変更
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

