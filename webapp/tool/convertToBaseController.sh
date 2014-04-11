#!/bin/sh
#
# convert single module to duplicatable module controllers
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

if [ "$1" = "" ];then
    echo "Usage $0 default_module_name target_module"
    echo
    exit
fi

if [ "$2" != "" ]; then
    mods=$2
else
    mods=`ls ../modules/`
    echo "Usage $0 default_module_name target_module"
    echo
    exit
fi

for mod in $mods; do

    toPascal $mod HYPHEN; MOD_PASCAL=$RESULT

    echo "$mod => $MOD_PASCAL"

    # 通常のコントローラディレクトリ
    DEFAULTCTR_DIR=../modules/$mod/controllers

    # ベースディレクトリがあるか
    BASECTR_DIR=$DEFAULTCTR_DIR/Base
    if [ ! -d $BASECTR_DIR ]; then
        # なければ作る
        echo "---> no base directory: $BASECTR_DIR"
        mkdir $BASECTR_DIR
        if [ $? != 0 ]; then
            exit
        fi
        echo "---> created"
    fi
    # カスタムディレクトリがあるか
    CUSTOMCTR_DIR=$DEFAULTCTR_DIR/Custom
    if [ ! -d $CUSTOMCTR_DIR ]; then
        # なければ作る
        echo "---> no custom directory: $CUSTOMCTR_DIR"
        mkdir $CUSTOMCTR_DIR
        if [ $? != 0 ]; then
            exit
        fi
        echo "---> created"
    fi

    for ctr in `ls ../modules/$mod/controllers/*.php`; do

        # Zend がアクセスするコントローラ名
        CLASS_ORIGNAME=`basename $ctr .php`
        if [ "$mod" != "$1" -a "$mod" != "default" ]; then
            CLASS_ORIGNAME="${MOD_PASCAL}_${CLASS_ORIGNAME}"
        fi 
        CLASS_BASENAME=`echo $CLASS_ORIGNAME| sed 's/Controller/BaseController/'`
        CLASS_CUSTNAME=`echo $CLASS_ORIGNAME| sed 's/Controller/CustomController/'`

        # ファイル名
        CLASS_ORIGFILE=`basename $ctr`
        CLASS_BASEFILE=`echo $CLASS_ORIGFILE | sed s/Controller/BaseController/`
        CLASS_CUSTFILE=`echo $CLASS_ORIGFILE | sed s/Controller/CustomController/`

        echo "-->$CLASS_ORIGNAME ($DEFAULTCTR_DIR/$CLASS_ORIGFILE)"
        echo "---->$CLASS_BASENAME ($BASECTR_DIR/$CLASS_BASEFILE)"
        echo "---->$CLASS_CUSTNAME ($CUSTOMCTR_DIR/$CLASS_CUSTFILE)"

        if [ ! -f $BASECTR_DIR/$CLASS_BASENAME ]; then
            echo "cp $DEFAULTCTR_DIR/$CLASS_ORIGFILE $BASECTR_DIR/$CLASS_BASEFILE"
            sed "s/${CLASS_ORIGNAME}/${CLASS_BASENAME}/" $DEFAULTCTR_DIR/$CLASS_ORIGFILE > $BASECTR_DIR/$CLASS_BASEFILE
        fi
        if [ ! -f $CUSTOMCTR_DIR/$CLASS_CUSTNAME ]; then
            echo "cp ./skel_dup/controller/SkeltonCustomController.php $CUSTOMCTR_DIR/$CLASS_CUSTFILE"
            cat ./skel_dup/controller/SkeltonCustomController.php |\
            sed "s/<{\$CustomClassName}>/${CLASS_CUSTNAME}/" |\
            sed "s/<{\$BaseClassName}>/${CLASS_BASENAME}/" |\
            sed "s/<{\$BaseFileName}>/${CLASS_BASEFILE}/" > $CUSTOMCTR_DIR/$CLASS_CUSTFILE
        fi

        echo "cp ./skel_dup/controller/SkeltonController.php $DEFAULTCTR_DIR/$CLASS_ORIGFILE"

            cat ./skel_dup/controller/SkeltonController.php |\
            sed "s/<{\$MOD_PASCAL}>/${MOD_PASCAL}/" |\
            sed "s/<{\$OrigClassName}>/${CLASS_ORIGNAME}/" |\
            sed "s/<{\$CustomFileName}>/${CLASS_CUSTFILE}/" |\
            sed "s/<{\$CustomClassName}>/${CLASS_CUSTNAME}/" |\
            sed "s/<{\$BaseClassName}>/${CLASS_BASENAME}/" |\
            sed "s/<{\$BaseFileName}>/${CLASS_BASEFILE}/" > $DEFAULTCTR_DIR/$CLASS_ORIGFILE

        echo
    done

done

