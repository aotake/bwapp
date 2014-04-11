#!/bin/sh
#
# convert to duplicatable lib structure
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
# 旧仕様の libs 構造を Base, Custom をつかった仕様に変換する
# 

LANG=C

. func.sh

if [ "$1" = "" ];then
    echo "Usage $0 default_module_name [target_module]"
    echo
    exit
fi

if [ "$2" != "" ]; then
    mods=$2
else
    echo "Usage $0 default_module_name target_module"
    exit;
    mods=`ls ../modules/`
fi

for mod in $mods; do

    toPascal $mod HYPHEN; MOD_PASCAL=$RESULT

    echo "$mod => $MOD_PASCAL"

    ORIGLIB_DIR=../modules/$mod/libs/${MOD_PASCAL}

    DEFAULTLIB_DIR=../modules/$mod/libs/default/${MOD_PASCAL}
    if [ ! -d $DEFAULTLIB_DIR ]; then
        # なければ作る
        echo "---> no base directory: $DEFAULTLIB_DIR"
        mkdir -p $DEFAULTLIB_DIR
        if [ $? != 0 ]; then
            echo "cannot create: $DEFAULTLIB_DIR"
            exit
        fi
        echo "---> created: $DEFAULTLIB_DIR"
        DIRS="Form Form/Validator Manager Model Vo"
        for d in $DIRS; do
            mkdir $DEFAULTLIB_DIR/$d
            if [ $? != 0 ]; then
                exit
            fi
            echo "---> created: $DEFAULTLIB_DIR/$d"
        done
    fi
    # オリジナルをコピー
    pushd ../modules/$mod/libs/
    if [ $? = 1 ]; then
        echo "---------> cannot pushd: ../modules/$mod/libs"
        popd
        exit
    fi
    tar -cf - ./${MOD_PASCAL} | (cd default; tar -xf -)
    popd

    BASELIB_DIR=../modules/$mod/libs/base/${MOD_PASCAL}Base
    if [ ! -d $BASELIB_DIR ]; then
        # なければ作る
        echo "---> no base directory: $BASELIB_DIR"
        mkdir -p $BASELIB_DIR
        if [ $? != 0 ]; then
            exit
        fi
        echo "---> created: $BASELIB_DIR"
        DIRS="Form Form/Validator Manager Model Vo"
        for d in $DIRS; do
            mkdir $BASELIB_DIR/$d
            if [ $? != 0 ]; then
                exit
            fi
            echo "---> created: $BASELIB_DIR/$d"
        done
    fi
    # カスタムディレクトリがあるか
    CUSTOMLIB_DIR=../modules/$mod/libs/custom/${MOD_PASCAL}Custom
    if [ ! -d $CUSTOMLIB_DIR ]; then
        # なければ作る
        echo "---> no custom directory: $CUSTOMLIB_DIR"
        mkdir -p $CUSTOMLIB_DIR
        if [ $? != 0 ]; then
            exit
        fi
        echo "---> created: $CUSTOMLIB_DIR"
        DIRS="Form Form/Validator Manager Model Vo"
        for d in $DIRS; do
            mkdir $CUSTOMLIB_DIR/$d
            if [ $? != 0 ]; then
                exit
            fi
            echo "---> created: $CUSTOMLIB_DIR/$d"
        done
    fi

    # Base にコピー
    pushd $ORIGLIB_DIR
    if [ $? = 1 ]; then
        echo "---------> cannot pushd: $ORIGLIB_DIR"
        popd
        exit
    fi
    TARGET=`find . -type f -print`
    popd
    for f in $TARGET; do
        echo "cp $ORIGLIB_DIR/$f $BASELIB_DIR/$f"
        MNGNAME=`basename $f .php`
        MODCODE="\$this->modname = basename\(dirname\(dirname\(dirname\(dirname\(__FILE__\)\)\)\)\);"
        MODCODE_REP="\$this->modname = basename\(dirname\(dirname\(dirname\(dirname\(dirname\(__FILE__\)\)\)\)\)\);"

        echo $f | grep "/Manager/" > /dev/null
        if [ $? = 0 ]; then
            CHKCODE="if\(!class_exists\(\"${MOD_PASCAL}Base_Manager_${MNGNAME}\", false\)\){\n\nclass ";
        else
            echo $f | grep "/Form/" | grep -v "/Form/Validator/" > /dev/null
            if [ $? = 0 ]; then
                CHKCODE="if\(!class_exists\(\"${MOD_PASCAL}Base_Form_${MNGNAME}\", false\)\){\n\nclass ";
            else
                CHKCODE="class "
            fi
        fi

        cat $ORIGLIB_DIR/$f |\
            sed "s/new ${MOD_PASCAL}_Manager/new ${MOD_PASCAL}Base_Manager/g" |\
            sed "s/new ${MOD_PASCAL}_Form/new ${MOD_PASCAL}Base_Form/g" |\
            sed "s/^class ${MOD_PASCAL}_/class ${MOD_PASCAL}Base_/" |\
            perl -pe "s/${MODCODE}/${MODCODE_REP}/" |\
            perl -pe "s/^class /\n${CHKCODE}/" > $BASELIB_DIR/$f

        # class_exists() コードを埋め込んでいたら閉じ括弧を追記
        if [ "$CHKCODE" != "class " ]; then
            echo "" >> $BASELIB_DIR/$f
            echo "}" >> $BASELIB_DIR/$f
        fi

        # フォームの時は Validator へのパスを require する
        cat $BASELIB_DIR/$f | grep "new ${MOD_PASCAL}Base_Form"
        if [ $? = 0 ]; then
            # 1. ファイルから "new XXX_Form_YYY" の行を抜き出し
            # 2. new XXX_Form の行から XXX_Form_YYY の文字列を抜き出し
            # 3. _ を / に置換し
            # 4. ".php" を付け足し
            # 5. basename でファイル名を取り出す
            VALIDATOR=`cat $BASELIB_DIR/$f | grep "new ${MOD_PASCAL}Base_Form" | sed "s/.*new \(${MOD_PASCAL}Base_Form_[a-zA-Z0-9_]*\).*/\1/" | sed "s/_/\//g"`.php
            FILE=`basename $VALIDATOR`
            REQUIRE="require_once dirname(__FILE__).\"\\/Validator\\/${FILE}\";"
            #cat $BASELIB_DIR/$f | sed "s/<?php/<?php\n${REQUIRE}/" > tmp
            cat $BASELIB_DIR/$f | perl -pe "s/<\?php/<\?php\n${REQUIRE}/" > tmp
            mv tmp $BASELIB_DIR/$f
        fi
    done

    # カスタム Manager, エントリ(デフォルト) Manager
    pushd $ORIGLIB_DIR/Manager
    if [ $? = 1 ]; then
        echo "---------> cannot pushd: $ORIGLIB_DIR/Manager"
        popd
        exit
    fi
    MANAGER_TARGET=`find . -type f -print`
    popd
    for m in $MANAGER_TARGET; do
        MANAGER_NAME=`basename $m .php`
        echo "cp ./skel_dup/Manager/SkeltonCustom.php $CUSTOMLIB_DIR/Manager/${MANAGER_NAME}.php"
        # カスタム
        cat ./skel_dup/Manager/SkeltonCustom.php | sed "s/<{\$ModuleName}>/${MOD_PASCAL}/g" | sed "s/<{\$ManagerName}>/${MANAGER_NAME}/g" > $CUSTOMLIB_DIR/Manager/${MANAGER_NAME}.php
        # デフォルト
        cat ./skel_dup/Manager/Skelton.php | sed "s/<{\$ModuleName}>/${MOD_PASCAL}/g" | sed "s/<{\$ManagerName}>/${MANAGER_NAME}/g" > $DEFAULTLIB_DIR/Manager/${MANAGER_NAME}.php
    done

    # カスタム Form
    pushd $ORIGLIB_DIR/Form
    FORM_TARGET=`find . -type f -print`
    popd
    for m in $FORM_TARGET; do
        FORM_NAME=`basename $m .php`
        echo "cp ./skel_dup/Form/SkeltonCustom.php $CUSTOMLIB_DIR/Form/${FORM_NAME}.php"
        cat ./skel_dup/Form/SkeltonCustom.php |\
            sed "s/<{\$ModuleName}>/${MOD_PASCAL}/g" |\
            sed "s/<{\$TableName}>/${FORM_NAME}/g" > $CUSTOMLIB_DIR/Form/${FORM_NAME}.php

        cat ./skel_dup/Form/Skelton.php |\
            sed "s/<{\$ModuleName}>/${MOD_PASCAL}/g" |\
            sed "s/<{\$TableName}>/${FORM_NAME}/g" > $DEFAULTLIB_DIR/Form/${FORM_NAME}.php

        #CLASS=`cat $BASELIB_DIR/Form/${FORM_NAME}.php | grep "^class ${MOD_PASCAL}Base" | sed "s/^class \([a-zA-Z0-9\_]*\).*/\1/" | sed "s/_/\//g"`
        #if [ "$CLASS" != "" ]; then
        #    TBL=`basename $CLASS`
        #    echo "CLASS = $CLASS, TBL=$TBL"
        #fi
    done

    # カスタム Validator
    pushd $ORIGLIB_DIR/Form/Validator
    VALIDATOR_TARGET=`find . -type f -print`
    popd
    for m in $VALIDATOR_TARGET; do
        VALIDATOR_NAME=`basename $m .php`
        echo "cp ./skel_dup/Form/SkeltonCustom.php $CUSTOMLIB_DIR/Form/Validator/${VALIDATOR_NAME}.php"
        cat ./skel_dup/Form/Validator/SkeltonCustom.php | sed "s/<{\$ModuleName}>/${MOD_PASCAL}/g" | sed "s/<{\$TableName}>/${VALIDATOR_NAME}/g" > $CUSTOMLIB_DIR/Form/Validator/${VALIDATOR_NAME}.php
        cat ./skel_dup/Form/Validator/Skelton.php | sed "s/<{\$ModuleName}>/${MOD_PASCAL}/g" | sed "s/<{\$TableName}>/${VALIDATOR_NAME}/g" > $DEFAULTLIB_DIR/Form/Validator/${FORM_NAME}.php
    done

done
exit

