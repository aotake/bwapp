#!/bin/sh
#
# generate vo code by skelton
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


## Vo ファイルをスケルトンからコピー
if [ "$target" = "all" -o "$target" = "vo" ]; then
    /bin/echo -n "===> put Vo/${pascalTbl}.php"
    CMD="cp  $SKEL_DIR/Vo/Skelton.php $VO_DIR/${pascalTbl}.php"
    /bin/echo $VO_DIR/${pascalTbl}.php >> $GENERATEFILES
    if [ -f $VO_DIR/${pascalTbl}.php ]; then
        /bin/echo 
        /bin/echo "already exist: $VO_DIR/${pascalTbl}.php"
        /bin/echo -n "Do you want to replace this file? [y/N]"
        read answer
        if [ "$answer" = "y" ]; then
            ${CMD}            /bin/echo ".....replaced."
            /bin/echo
        else
            /bin/echo ".....skip."
            /bin/echo
        fi
    else
        ${CMD}
        /bin/echo ".....created."
    fi
fi
