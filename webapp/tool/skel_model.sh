#!/bin/sh
#
# generate model code by skelton
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

## Model ファイルをスケルトンからコピー
if [ "$target" = "all" -o "$target" = "model" ]; then
    /bin/echo -n "===> put Model/${pascalTbl}.php"
    /bin/echo $MODEL_DIR/${pascalTbl}.php >> $GENERATEFILES
    CMD="cp $SKEL_DIR/Model/Skelton.php $MODEL_DIR/${pascalTbl}.php"
    if [ -f $MODEL_DIR/${pascalTbl}.php ]; then
        /bin/echo 
        /bin/echo "already exist: $MODEL_DIR/${pascalTbl}.php"
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
fi  
