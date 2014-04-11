#/bin/sh
#
# replace git repository url text
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

# [ja]
# リポジトリ URL 書き換えスクリプト
#
# トップディレクトリ以下にある .git/config の url 行が
# webapp/config/custom/cloneproject.csv の GIT リポジトリと異なる場合、
# cloneproject.csv のリポジトリ URL で書き換える。
#
# cloneproject.csv に記載された GIT リポジトリが存在しなかった場合は
# cloneproject.csv の url から git clone する。
#
# cloneproject.csv に記載された内容に対応するプロジェクトのディレクトリが
# 存在するけど .git/config が見つからなかった場合は何もせず処理をスキップする。
# この場合手動でリポジトリの clone など調整をする。
#
# □ 想定
# 1. サーバ等あわせたい環境の cloneproject.csv を webapp/config/custom に clone 済み
# 2. このスクリプトを実行後 cloneproject.sh を実行する（念のため不要）
# 3. cloneproject.sh 後 "gitcheck.sh fetch" する (pull は怖い)
#
# [/ja]

# 出力に色をつける設定(See: /etc/rc.d/init.d/functions)
RES_COL=73
MOVE_TO_COL="echo -en \\033[${RES_COL}G"
SETCOLOR_SUCCESS="echo -en \\033[1;32m"
SETCOLOR_FAILURE="echo -en \\033[1;31m"
SETCOLOR_WARNING="echo -en \\033[1;33m"
SETCOLOR_DUPLICATE="echo -en \\033[1;35m"
SETCOLOR_NORMAL="echo -en \\033[0;39m"

TOP=`(cd ../../; /bin/pwd)`
# git clone 実行関数
function replace_git_config() {
    TYPE=$1
    GIT=$2
    DIR=$3

    # まずトップディレクトリへ
    cd $TOP
    # タイプにあわせて移動
    if [ "$TYPE" = "layout" ]; then
        cd html/layout
    elif [ "$TYPE" = "module" ]; then
        # gencloneproject.sh で static_html の処理をしていないため
        # module に分類されてしまっているので、例外処理を入れる
        echo $GIT | grep "static_page" > /dev/null
        if [ $? = 0 ]; then
            cd html/
        else
            cd webapp/modules
        fi
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

    $ECHO -n ">> $GIT"
    # git 実行
    DATE=`/bin/date +%Y%m%d_%H%M%S`
    if [ -d $DIR ]; then
        # ディレクトリはあるが、.git/config が無ければ abort する
        if [ ! -f $DIR/.git/config ]; then
            $SETCOLOR_FAILURE
            $ECHO "..... abort.(no .git/config [$DIR])"
            $SETCOLOR_NORMAL
            return;
        fi
        # あったら、.git/config のバックアップをとって書き換える
        #echo "---------> rewrite .git/config"
        cp $DIR/.git/config $DIR/.git/config.$DATE
        cp /dev/null $DIR/.git/config.new
        cat $DIR/.git/config | while read line
        do
            echo $line | grep "url =" > /dev/null
            if [ $? = 0 ]; then
                echo "        url = $GIT" >> $DIR/.git/config.new
            else
                echo $line | grep "\[.*\]" > /dev/null
                if [ $? = 0 ]; then
                    echo $line >> $DIR/.git/config.new
                else
                    echo "        $line" >> $DIR/.git/config.new
                fi
            fi
        done
        mv $DIR/.git/config.new $DIR/.git/config
        if [ $? = 1 ]; then
            $SETCOLOR_FAILURE
            $ECHO "..... ** could not be replaced **"
            $SETCOLOR_NORMAL
        else
            $ECHO ".....replaced."
        fi
    else
        # なかったらクローンする
        #echo "===> git clone $GIT $DIR"
        echo "..... repository not found, start to clone:"
        git clone $GIT $DIR
    fi

    return
}

### ここから

TARGET_DIR=../../
GIT_RESOURCES=`find $TARGET_DIR -name .git -type d -exec dirname {} \;`
GIT_SERVER=$1
CSV=../config/custom/cloneproject.csv
ECHO=/bin/echo

if [ -f tmp ]; then
    rm -f tmp
else
    touch tmp
fi

for d in $GIT_RESOURCES; do
    if [ "$d/" = "${TARGET_DIR}" ]; then
        continue;
    fi
    $ECHO -n "."
    MOD=`basename $d`
    URL=`cat $d/.git/config | grep url | sed "s/url = \(.*\)/\1/" | awk '{print $1}'`
    if [ "$URL" = "" ]; then
        $ECHO "====> no url: ".$d
        continue
    fi

    $ECHO $URL | grep "templates_custom" > /dev/null
    if [ $? = 0 ]; then
        TYPE=templates_custom
    else
        $ECHO $URL | grep "/config" > /dev/null
        if [ $? = 0 ]; then
            $ECHO $URL | grep "/modules" > /dev/null
            if [ $? = 0 ]; then
                TYPE=module_config
            else
                TYPE=webapp_config
            fi
        else
            $ECHO $URL | grep "bmathlog_custom" > /dev/null
            if [ $? = 0 ]; then
                $ECHO $URL | grep "/controller" > /dev/null
                if [ $? = 0 ]; then
                    TYPE=custom_controller
                else 
                    $ECHO $URL | grep "/libs" > /dev/null
                    if [ $? = 0 ]; then
                        TYPE=custom_libs
                    else
                        $ECHO $URL | grep "/layout" > /dev/null
                        if [ $? = 0 ]; then
                            TYPE=layout
                        else
                            $ECHO $URL | grep "/css" > /dev/null
                            if [ $? = 0 ]; then
                                TYPE=custom_css
                            else
                                TYPE=module
                            fi
                        fi
                    fi
                fi
            else
                $ECHO $URL | grep "bmathlog_.*module" > /dev/null
                if [ $? = 0 ]; then
                    TYPE=module
                else
                    TYPE=layout
                fi
            fi
        fi
    fi
    if [ "$GIT_SERVER" ]; then
        $ECHO "$TYPE, $GIT_SERVER:$URL, $MOD" >> tmp
    else
        $ECHO "$TYPE, $URL, $MOD" >> tmp
    fi
done


# 苦肉のソートもどき
TMP=tmp.tmp
cp /dev/null $TMP
cat tmp | grep "^webapp_config," | sort >> $TMP
cat tmp | grep "^layout," | sort >> $TMP
cat tmp | grep "^module," | sort >> $TMP
cat tmp | grep "^module_config," | sort >> $TMP
cat tmp | grep "^templates_custom," | sort >> $TMP
cat tmp | grep "^custom_controller," | sort >> $TMP
cat tmp | grep "^custom_libs," | sort >> $TMP
cat tmp | grep "^custom_css," | sort >> $TMP
mv $TMP tmp

echo 
# CSV と比較して現サイトツリーに該当するリポジトリがなければ
# [NOT FOUND] と表示する
cp /dev/null notfound.log
cat $CSV | while read line
do
    grep "$line" tmp > /dev/null
    if [ $? = 1 ]; then
        $SETCOLOR_FAILURE
        echo "[NOT FOUND] $line"
        $SETCOLOR_NORMAL
        echo $line >> notfound.log
    else
        $SETCOLOR_SUCCESS
        echo -en "[found] "
        $SETCOLOR_NORMAL
        $ECHO $line
    fi
done

sleep 1
#
# 実際のリポジトリと比較
#
# 1. webapp_config
# 2. layout 
# 3. module
# 4. module_config
# 5. templates_custom
# 6. custom_controller
# 7. custom_libs
# 8. custom_css
TARGET="webapp_config layout module module_config templates_custom custom_controller custom_libs custom_css"
for t in $TARGET; do
    $ECHO "----> $t"
    cat notfound.log | grep "^$t, " | sed -e "s/,/ /g" | awk '{ printf("%s %s %s\n", $1,$2,$3); }' |\
    while read target_line
    do
        replace_git_config $target_line
    done
done
