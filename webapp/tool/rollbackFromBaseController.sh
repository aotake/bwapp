#!/bin/sh
#
# rollbackFromBaseController.sh
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
# 

LANG=C

. func.sh

#if [ "$1" = "" ];then
#    echo "Usage $0 [target_module]"
#    echo
#    exit
#fi

if [ "$1" != "" ]; then
    mods=$1
else
    mods=`ls ../modules/`
fi

for mod in $mods; do

    # 通常のコントローラディレクトリ
    DEFAULTCTR_DIR=../modules/$mod/controllers

    # ベースディレクトリがあるか
    BASECTR_DIR=$DEFAULTCTR_DIR/Base
    if [ -d $BASECTR_DIR ]; then
        rm -fr $BASECTR_DIR
    fi
    # カスタムディレクトリがあるか
    CUSTOMCTR_DIR=$DEFAULTCTR_DIR/Custom
    if [ -d $CUSTOMCTR_DIR ]; then
        rm -fr $CUSTOMCTR_DIR
    fi

    pushd ../modules/$mod
    rm -f *.*
    git checkout .
    popd

done

