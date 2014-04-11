#!/bin/sh
#
# directory check for skelton
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
# LIBDIR があるかをチェックし、なければ作成
# サブディレクトリもなければ自動生成する
#

if [ ! -d $LIBDIR ]; then
    /bin/echo "Not Found: $LIBDIR"
    #/bin/echo "----> Please check LIBDIR path"
    LIBTOP=`dirname $LIBDIR`
    if [ ! -d $LIBTOP ]; then # libs directory がない
        /bin/echo "Not Found: $LIBTOP"
        mkdir $LIBTOP
        if [ ! -d  $LIBTOP ]; then
            /bin/echo "--->Cannot create directory: $LIBTOP"
            exit
        else
            /bin/echo "--->Create directory: $LIBTOP"
        fi
        mkdir $LIBDIR
        #if [ $? ]; then
        if [ ! -d $LIBDIR ]; then
            /bin/echo "--->Cannot create directory: $LIBDIR"
            exit
        else
            /bin/echo "--->Create directory: $LIBDIR"
        fi
    fi

    _DIRS="$CONTROLLER_DIR $VIEW_DIR_TOP $MANAGER_DIR $FORM_DIR $VALIDATOR_DIR $MODEL_DIR $VO_DIR";
    for d in $_DIRS; do
        if [ ! -d $d ]; then
            /bin/echo "Not Found: $d"
            mkdir $d
            if [ ! -d $d ]; then
                /bin/echo "--->Cannot create directory: $d"
                exit
            else
                /bin/echo "--->Create directory: $d"
            fi
        fi
    done
fi

_DIRS="$HANDLER_DIR $MANAGER_DIR $FORM_DIR $VALIDATOR_DIR $MODEL_DIR $VO_DIR"
for d in $_DIRS; do
    if [ ! -d $d ]; then
        mkdir $d
    fi
done

