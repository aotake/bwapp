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

#
# このサイト以下にある GIT プロジェクトに対して一括で checkout する
#

# このスクリプトがあるディレクトリに移動
THIS_SCRIPT_PATH=$(cd $(dirname $0); pwd)
cd $THIS_SCRIPT_PATH;

# 関数読み込み
. $THIS_SCRIPT_PATH/gitcheck_func.sh

#各種設定
GIT=/usr/bin/git
ECHO=/bin/echo
TOOL_DIR=`/bin/pwd`
TARGET_DIR=../..
TMP_FILE=.tmp
GIT_RESOURCES=`find $TARGET_DIR -name .git -type d -exec dirname {} \;`

if [ "$1" = "-b" ]; then
    BRANCH=$2
    SOURCE=$3
else 
    BRANCH=$1
fi

if [ "$BRANCH" = "" ]; then
    echo
    echo "Usage: $0 [-b] branch_name"
    echo
    exit
fi

# 出力に色をつける設定(See: /etc/rc.d/init.d/functions)
RES_COL=70
MOVE_TO_COL="echo -en \\033[${RES_COL}G"
SETCOLOR_SUCCESS="echo -en \\033[1;32m"
SETCOLOR_FAILURE="echo -en \\033[1;31m"
SETCOLOR_WARNING="echo -en \\033[1;33m"
SETCOLOR_DUPLICATE="echo -en \\033[1;35m"
SETCOLOR_NORMAL="echo -en \\033[0;39m"

if [ -f $TMP_FILE ]; then
    rm -f $TMP_FILE
fi

$ECHO
$ECHO "-------gitcheck--------"
$ECHO
for target_dir in $GIT_RESOURCES; do
    if [ "$target_dir" = "../.." ]; then
        continue
    fi

    $ECHO "====> $target_dir"
    $ECHO -n "-------> git checkout $@"
    pushd $target_dir >& /dev/null

    # カレントのブランチ名を確認
    GIT_CURRENT_BRANCH=$($GIT branch | grep '\*' | awk '{ print $2 }')
    $MOVE_TO_COL
    if [ "$BRANCH" = "$GIT_CURRENT_BRANCH" ]; then
        $SETCOLOR_WARNING
        $ECHO " [WARN]"
        $SETCOLOR_NORMAL
        echo "----> 既に $BRANCH で作業中です"
    else
        $GIT checkout $@ >& $TMP_FILE
        grep "error" $TMP_FILE > /dev/null
        if [ $? = 0 ]; then
            $SETCOLOR_FAILURE
            $ECHO " [ NG ]"
            $SETCOLOR_NORMAL
            cat $TMP_FILE
        else
            $SETCOLOR_SUCCESS
            $ECHO " [ OK ]"
            $SETCOLOR_NORMAL
        fi
        rm -f $TMP_FILE
    fi
    popd >& /dev/null
done

