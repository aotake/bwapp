#!/bin/sh
#
# generate view code by skelton
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

## view ファイルを生成してコピー
if [ "$target" = "all" -o "$target" = "view" ]; then

    index_actions="index detail"
    #admin_actions="index new new_complete edit edit-confirm edit-regist delete delete-do"
    admin_actions="index detail new new_complete edit edit-confirm edit-regist delete delete-do"
    for isadmin in 0 1; do
        if [ $isadmin = 1 ]; then
            VDIR=$origTbl"-admin"
            _actions=$admin_actions
        else
            #VDIR="index"
            VDIR=$origTbl
            _actions=$index_actions
        fi

        if [ ! -d $VIEW_DIR_TOP/$VDIR ]; then
            mkdir $VIEW_DIR_TOP/$VDIR
        fi

        for view in $_actions; do

            #/bin/echo -n "===> put templates/${VDIR}/${view}.html"
            /bin/echo -n "===> put templates/${VDIR}/${view}.html"
            php ./dbconf.php $origMod $origTbl View $isadmin $view > tmp
            CMD="cp tmp $VIEW_DIR_TOP/${VDIR}/${view}.html"
            /bin/echo $VIEW_DIR_TOP/${VDIR}/${view}.html >> $GENERATEFILES
            if [ -f $VIEW_DIR_TOP/${VDIR}/${view}.html ]; then
                /bin/echo 
                /bin/echo "already exist: $VIEW_DIR_TOP/${VDIR}/${view}.html"
                /bin/echo -n "Do you want to replace this file? [y/N]"
                read answer
                if [ "$answer" = "y" ]; then
                    ${CMD}
                    /bin/echo ".....replaced."
                    /bin/echo
                else
                    /bin/echo ".....skip."
                    /bin/echo
                fi
            else
                ${CMD}
                /bin/echo ".....created."
            fi
        done
    done


fi

