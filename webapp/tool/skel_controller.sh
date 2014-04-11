#!/bin/sh
#
# generate code from controller skelton
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


## controller ファイルを生成してコピー
if [ "$target" = "all" -o "$target" = "controller" ]; then
    for isadmin in 0 1; do
        if [ $isadmin =  1 ]; then
            _FILE=$CONTROLLER_DIR/${pascalTbl}AdminController.php
        else
            _FILE=$CONTROLLER_DIR/${pascalTbl}Controller.php
        fi

        #/bin/echo "php ./dbconf.php $origMod $origTbl Controller $isadmin > tmp"
        /bin/echo -n "===> put $_FILE"
        php ./dbconf.php $origMod $origTbl Controller $isadmin > tmp
        CMD="cp tmp $_FILE"
        /bin/echo $_FILE >> $GENERATEFILES
        if [ -f $_FILE ]; then
            /bin/echo 
            /bin/echo "already exist: $_FILE"
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
fi

