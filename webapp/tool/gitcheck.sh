#/bin/bash
#
# execute git operation totally for all git project
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

# このサイト以下にある GIT プロジェクトにたいして一括操作をする
#

# このスクリプトがあるディレクトリに移動
THIS_SCRIPT_PATH=$(cd $(dirname $0); pwd)
cd $THIS_SCRIPT_PATH;

# 関数読み込み
. gitcheck_func.sh

#各種設定
ECHO=/bin/echo
TOOL_DIR=`/bin/pwd`
TARGET_DIR=../..
TMP_FILE=.tmp
GIT_RESOURCES=`find $TARGET_DIR -name .git -type d -exec dirname {} \;`
COMMAND=$1
KEYWORD=$2

# 出力に色をつける設定(See: /etc/rc.d/init.d/functions)
RES_COL=70
MOVE_TO_COL="echo -en \\033[${RES_COL}G"
SETCOLOR_SUCCESS="echo -en \\033[1;32m"
SETCOLOR_FAILURE="echo -en \\033[1;31m"
SETCOLOR_WARNING="echo -en \\033[1;33m"
SETCOLOR_DUPLICATE="echo -en \\033[1;35m"
SETCOLOR_NORMAL="echo -en \\033[0;39m"

# 受付コマンドチェック
ALLOW_CMD="status|pull|push|log|tag|stash|fetch|branch"
echo $COMMAND | grep -E "$ALLOW_CMD" > /dev/null
if [ $? != 0 ]; then
    $ECHO "Usage: $0 [$ALLOW_CMD]"
    exit
fi

if [ "$COMMAND" = "pull" ]; then
    $ECHO "command: pull"
    $ECHO "    y: continue pull action"
    $ECHO "    f or non-y key: turn to fetch action"
    $ECHO -n "---> pull ok? or fetch only? [y|(f)] "
    read ans
    if [ "$ans" != y ];then
        COMMAND=`echo $@ | sed -s 's/pull/fetch/'`
        $SETCOLOR_WARNING
        $ECHO "===> change command to [ fetch ] "
        $SETCOLOR_NORMAL
    fi
fi

# status でディレクトリ指定があれば(KEYWORD に値があれば）
# チェック対象プロジェクトをそのディレクトリ以下に変更する
if [ "$COMMAND" = "status" -a "$KEYWORD" != "" ]; then
    TARGET_DIR=$KEYWORD
    GIT_RESOURCES=`find $TARGET_DIR -name .git -type d -exec dirname {} \;`
fi

$ECHO
$ECHO "-------gitcheck--------"
$ECHO
for target_dir in $GIT_RESOURCES; do
    $ECHO -n "====> $target_dir"
    pushd $target_dir >& /dev/null
    #############################################################
    # git branch
    #############################################################
    if [ "$COMMAND" = "branch" ]; then
        $ECHO
        git $COMMAND $KEYWORD
    #############################################################
    # git log
    #############################################################
    elif [ "$COMMAND" = "log" ]; then
        if [ "$KEYWORD" != "" ]; then
            git $COMMAND -3 > $TMP_FILE
            #grep "$KEYWORD" tmp >& /dev/null
            res=`hasKeyword $TMP_FILE $KEYWORD`
            if [ $res = 0 ];then
                $ECHO " .. *** found ***"
                cat $TMP_FILE
                rm -f $TMP_FILE
                exit
            fi
            $ECHO
        else
            $ECHO
            GIT_PAGER=cat git $COMMAND -1
            $ECHO
            $ECHO
        fi
    #############################################################
    # git stash list
    #############################################################
    elif [ "$COMMAND" = "stash" ]; then
        git stash list >& $TMP_FILE
        $MOVE_TO_COL
        if [ -s $TMP_FILE ]; then
            $SETCOLOR_WARNING
            $ECHO " [WARN]"
            cat $TMP_FILE
        else
            $SETCOLOR_SUCCESS
            $ECHO " [NONE]"
            cat $TMP_FILE
        fi
        $SETCOLOR_NORMAL
    #############################################################
    # git status
    #############################################################
    elif [ "$COMMAND" = "status" ]; then
        # コマンド実行結果保存用ディレクトリ
        if [ ! -d $TOOL_DIR/.tmp ]; then
            mkdir $TOOL_DIR/.tmp
        fi
        # 結果保存用ファイル名作成
        CHK_FILE=`$ECHO $target_dir | sed -e "s/\//___/g" | sed -e "s/^\.\.___\.\./sitetop/"`
        CHK_PATH=$TOOL_DIR/.tmp/$CHK_FILE
        # 複製モジュールで複製されていたらスキップ？
        THIS_PATH=`/bin/pwd`
        REPO_PATH=`cat .git/config | grep url | sed -e "s/.*url = \(.*\)/\\1/"`
        $ECHO $REPO_PATH | grep -E "libs/custom|/config" > /dev/null ; CUST_REPO=$? # カスタムライブラリなら 0 がセットされる
        test -f tool/convGit.sh; CHK_CONVERTER=$?                                   # コンバータがあれば 0 なければ 1
        REPO_DIR=`basename $REPO_PATH`
        THIS_DIR=`basename $THIS_PATH`
        if [ "$REPO_DIR" != "$THIS_DIR" -a $CHK_CONVERTER = 0 -a "$CUST_REPO" != 0 ]; then
            # リポジトリとカレントでディレクトリ名が異なり、コンバータをもっていて、かつカスタムリポジトリではない
            $MOVE_TO_COL
            $SETCOLOR_DUPLICATE
            $ECHO "[ DP ]"
            $ECHO "Repositry Dirname: $REPO_DIR"
            $ECHO "Module Dirname: $THIS_DIR"
            $ECHO "cust dir: $CUST_REPO"
            $ECHO "----> This module is duplicated. Pleas check manually."
            $ECHO 
            $SETCOLOR_NORMAL
        else
            # git status 実行
            git $COMMAND >& $CHK_PATH
            # チェック
            res=`repositoryChanged $CHK_PATH`
            if [ $res = 0 ]; then
                $MOVE_TO_COL
                $SETCOLOR_WARNING
                $ECHO "[WARN]"
                cat $CHK_PATH
                $ECHO
                $ECHO
            else
                $MOVE_TO_COL
                $SETCOLOR_SUCCESS
                $ECHO "[ OK ]"
            fi
            $SETCOLOR_NORMAL
        fi
    #############################################################
    # git status
    #############################################################
    elif [ "$COMMAND" = "tag" ]; then
        git $@ >& $TMP_FILE
        $MOVE_TO_COL
        grep "already exists" $TMP_FILE > /dev/null
        if [ $? = 0 ]; then
            $SETCOLOR_WARNING
            $ECHO " [WARN]"
            cat $TMP_FILE
        else
            $SETCOLOR_SUCCESS
            $ECHO " [DONE]"
            cat $TMP_FILE
        fi
        $SETCOLOR_NORMAL
    #############################################################
    # git push
    #############################################################
    elif [ "$COMMAND" = "push" ]; then
        $ECHO
        git $@
    #############################################################
    # git fetch
    #############################################################
    elif [ "$COMMAND" = "fetch" ]; then
        git fetch >& $TMP_FILE
        $MOVE_TO_COL
        if [ -s $TMP_FILE ]; then
            $SETCOLOR_WARNING
            $ECHO " [WARN]"
            cat $TMP_FILE
            $SETCOLOR_NORMAL
        else
            $SETCOLOR_SUCCESS
            $ECHO " [ OK ]"
            $SETCOLOR_NORMAL
        fi
    #############################################################
    # git [push|tag|log|status] 以外
    #############################################################
    else
        git $COMMAND >& $TMP_FILE
        #grep "Already up-to-date" tmp > /dev/null
        res=`alreadyUpToDate $TMP_FILE`
        $MOVE_TO_COL
        if [ $res = 0 ]; then
            $SETCOLOR_SUCCESS
            $ECHO " [ OK ]"
            #$ECHO "grep res: $res" # for debug
            #cat $TMP_FILE     # for debug
            $SETCOLOR_NORMAL
        else
            #grep -i "fatal" $TMP_FILE > /dev/null
            res=`hasFatal $TMP_FILE`
            if [ $res = 0 ]; then
                $SETCOLOR_FAILURE
                $ECHO " [ NG ]"
            else
                grep -i -E "error|CONFLICT" $TMP_FILE > /dev/null
                if [ $? = 0 ]; then
                    $SETCOLOR_FAILURE
                    $ECHO " [ NG ]"
                else
                    $SETCOLOR_SUCCESS
                    $ECHO " [ OK ]"
                fi
            fi
            cat $TMP_FILE
            $SETCOLOR_NORMAL
        fi
    fi
    rm -fr $TMP_FILE
    popd >& /dev/null
done

# git status で作った一時ディレクトリがあれば削除
if [ -d $TOOL_DIR/.tmp ]; then
    rm -fr $TOOL_DIR/.tmp
fi

