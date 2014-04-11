#!/bin/bash
#
# text utility script for bash
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

function toSnake()
{
    argStr=$1
    delim=$2
    if [ "$delim" = "" ]; then
        delim="_"
    elif [ "$delim" = "HYPHEN" ]; then
        delim="-"
    else
        delim="_"
    fi

    separated=`/bin/echo $argStr | sed "s/\([A-Z]\)/ \\1/g"`
    RESULT=""
    for word in $separated; do
        substr=`/bin/echo "${word:0:1}" | tr '[A-Z]' '[a-z]'`
        tmp=`/bin/echo "${word}" | sed "s/^./${substr}/g"`
        if [ "$RESULT" = "" ]; then
            RESULT=$tmp
        else
            RESULT=${RESULT}_$tmp
        fi
    done
}

# return camelStr0 : string
function toPascal()
{
    argStr=$1
    delim=$2
    if [ "$delim" = "" ]; then
        delim="_"
    elif [ "$delim" = "HYPHEN" ]; then
        delim="-"
    else
        delim="_"
    fi

    separated=`/bin/echo $argStr | sed -e "s/$delim/ /g"`
    RESULT=""
    for word in $separated; do
        substr=`/bin/echo "${word:0:1}" | tr '[a-z]' '[A-Z]'`
        tmp=`/bin/echo "${word}" | sed "s/^./${substr}/g"`
        RESULT=$RESULT$tmp
    done
}

# return RESULT : string
function toCamel()
{
    argStr=$1
    delim=$2
    if [ "$delim" = "" ]; then
        delim="_"
    fi
    #separated=`/bin/echo $argStr| sed -e 's/_/ /g'`
    separated=`/bin/echo $argStr | sed -e "s/$delim/ /g"`
    RESULT=""
    for word in $separated; do
        substr=`/bin/echo "${word:0:1}" | tr '[a-z]' '[A-Z]'`
        tmp=`/bin/echo "${word}" | sed "s/^./${substr}/g"`
        if [ "$RESULT" = "" ]; then
            RESULT=$tmp
        else
            RESULT=${RESULT}_$tmp
        fi
    done
}

# return RESULT : string
function getModelName()
{
    mod=$1
    tbl=$2

    toPascal $mod; ModuleName=$RESULT
    toPascal $tbl; TableName=$RESULT

    RESULT="${ModuleName}_Model_${TableName}"
}
