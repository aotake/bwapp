#!/bin/sh
#
# dump production database and into current environment database
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

ECHO=/bin/echo

# バックアップ対象環境
TARGET=production

## ターゲット環境の DB ユーザ名を取得
eval `php loadconf.php $TARGET`
PRD_DBUSER=$DBUSER

## カレント環境の DB 関連データを取得
eval `php loadconf.php`
if [ "$TARGET" = "$APPLICATION_ENV" ]; then
    echo "ERROR: same environment -> target = $TARGET, current = $APPLICATION_ENV"
    exit
fi
DEV_DBUSER=$DBUSER


$ECHO "backup $TARGET"
/bin/sh ./dbdump.sh $TARGET > $TARGET.dump

$ECHO "import $TARGET data"

cat $TARGET.dump | sed "s/$PRD_DBUSER/$DEV_DBUSER/" | /bin/bash ./console.sh
$ECHO -n "remove backupdata? [(n)/y]"
read ans
if [ "$ans" = "y" ]; then
    $ECHO "rm -f $TARGET.dump"
    rm -f $TARGET.dump
fi
$ECHO "done."

# banner
# cd WEBAPP/modules/banner/; tar cf - ./uploads/ | (cd /home/web/development/webapp/modules/banner/; tar -xf -)
