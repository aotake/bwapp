#!/bin/sh
#
# prepare sql statements for alter.php
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


ENV=`php ./alter.php`
eval $ENV

MODULE=$1
SQL=$2

TMPFILE=$$.sql

if [ "$MODULE" = "" ]; then
    echo "Usage: $0 <MODULE_NAME> <SQL_FILE>"
    exit
fi

if [ "$SQL" = "" ]; then
    echo "Usage: $0 <MODULE_NAME> <SQL_FILE>"
    exit
fi
if [ ! -f $SQL ]; then
    echo "Error: not found: $SQL"
    echo "Usage: $0 <MODULE_NAME> <SQL_FILE>"
    exit
fi


cat $SQL | sed -e "s/<PREFIX>/$PREFIX/" | sed -e "s/<MODULE>/$MODULE/" | sed -e "s/<CHARSET>/$CHARSET/" > $TMPFILE
cat $TMPFILE

/bin/echo -n "continue? [y/n] "
read ans
if [ "$ans" != "y" ]; then
    echo "....canceled"
    rm -f $TMPFILE
    exit
fi

cat $SQL | sed -e "s/<PREFIX>/$PREFIX/" | sed -e "s/<MODULE>/$MODULE/" | sed -e "s/<CHARSET>/$CHARSET/" |\
$CMD

rm -f $TMPFILE
echo "....done"
