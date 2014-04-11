#!/bin/bash
#
# execute 'git clone' with csv
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
# [このスクリプト]
# csv 形式で記述したサイトで利用する git project の clone を一括取得する
#

# 読み込み CSV ファイル名(このスクリプトの拡張子sh を csv にしたもの)
CSV=../config/custom/`basename $0 .sh`.csv

# 読み込み CSV が無ければメッセージを出して終了
if [ ! -f "$CSV" ]; then
    echo "Not found siteproject.csv"
    echo "--> please copy from siteproject.sample.csv to $CSV"
    exit
fi

# トップディレクトリのフルパスを取得
TOP=`(cd ../../; /bin/pwd)`

ECHO=/bin/echo

# 出力に色をつける設定(See: /etc/rc.d/init.d/functions)
RES_COL=73
MOVE_TO_COL="echo -en \\033[${RES_COL}G"
SETCOLOR_SUCCESS="echo -en \\033[1;32m"
SETCOLOR_FAILURE="echo -en \\033[1;31m"
SETCOLOR_WARNING="echo -en \\033[1;33m"
SETCOLOR_DUPLICATE="echo -en \\033[1;35m"
SETCOLOR_NORMAL="echo -en \\033[0;39m"

# git clone 実行関数
function git_clone() {
    TYPE=$1
    GIT=$2
    DIR=$3

    # まずトップディレクトリへ
    cd $TOP
    # タイプにあわせて移動
    if [ "$TYPE" = "layout" ]; then
        cd html/layout
    elif [ "$TYPE" = "module" ]; then
        cd webapp/modules
    elif [ "$TYPE" = "webapp_config" ]; then
        cd webapp/config
    elif [ "$TYPE" = "module_config" ]; then
        _DIRNAME=`dirname $GIT`
        MODNAME=`basename $_DIRNAME`
        cd webapp/modules/$MODNAME/config
    elif [ "$TYPE" = "templates_custom" ]; then
        _DIRNAME=`dirname $GIT`
        MODNAME=`basename $_DIRNAME`
        cd webapp/modules/$MODNAME
    elif [ "$TYPE" = "custom_controller" ]; then
        _DIRNAME=`dirname $GIT`
        MODNAME=`basename $_DIRNAME`
        cd webapp/modules/$MODNAME/controllers
    elif [ "$TYPE" = "custom_libs" ]; then
        _DIRNAME=`dirname $GIT`
        MODNAME=`basename $_DIRNAME`
        cd webapp/modules/$MODNAME/libs
        if [ ! -d custom ]; then
            mkdir custom
        fi
        cd custom
    elif [ "$TYPE" = "custom_css" ]; then
        _DIRNAME=`dirname $GIT`
        MODNAME=`basename $_DIRNAME`
        if [ ! -d webapp/modules/$MODNAME/css ]; then
            mkdir webapp/modules/$MODNAME/css
        fi
        cd webapp/modules/$MODNAME/css
    fi
    $ECHO -n "$GIT"
    # git 実行
    if [ -d $DIR ]; then
        #echo "===> already exists"
        $MOVE_TO_COL
        $SETCOLOR_SUCCESS
        $ECHO "[EXISTS]"
    else
        #echo "===> git clone $GIT $DIR"
        $MOVE_TO_COL
        $SETCOLOR_WARNING
        $ECHO "[ NONE ]"
        git clone $GIT $DIR

        # -- convGit.sh があれば from する
        GIT_DIRNAME=`basename $GIT | sed "s/.git//"`
        if [ "$GIT_DIRNAME" != "$DIR" ]; then
            if [ -f $DIR/tool/convGit.sh ]; then
                echo "=====> $DIR/tools/convGit.sh from"
                cd $DIR/tool
                bash ./convGit.sh from
            fi
        fi
    fi
    $SETCOLOR_NORMAL
}

# CSV をパースして git_clone を呼び出す
grep -v "^#" $CSV |\
sed -e "s/,/ /g" |\
awk '{ printf("%s %s %s\n", $1,$2,$3); }'  |\
while read line
do
    git_clone $line
done

