#!/bin/sh
#
# modgit
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


# モジュール内の全 GIT プロジェクトに対して同一処理をする

ALLOW_CMD="log|tag|status|branch|repos"

echo $1 | grep -E "$ALLOW_CMD" > /dev/null
if [ $? = 1 ]; then
    echo "Usage: $0 [$ALLOW_CMD]"
    exit
fi

# log は cat で出力する
if [ "$1" = "log" ]; then
    export GIT_PAGER=cat
fi

target=". ./config/custom ./templates_custom ./controllers/Custom ./libs/custom"
# repos のときは各リポジトリの URL を表示する
if [ "$1" = "repos" ]; then
    for t in $target; do
        if [ ! -d $t ]; then
            continue;
        fi
        echo $t
        grep url $t/.git/config
    done
    exit
fi

# git コマンドを実行
for t in $target; do
    if [ ! -d $t ]; then
        continue;
    fi
    echo "============================================="
    echo "[$t]: git $@ "
    echo "---------------------------------------------"
    if [ "$t" = "." ]; then
        git $@
    else
        pushd $t > /dev/null
        git $@
        popd > /dev/null
    fi
    echo
    echo
done
