#!/bin/sh
#
# database backup script
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

# バッチで DB のバックアップを取るスクリプト
#
# 1. BACKUP_DIR にバックアップ先のディレクトリパスを指定する
# 2. BZIP2 に bzip2 コマンドのパスを指定する

target=$1
if [ "$target" = "" ]; then
    echo "Usage: $0 [production|development|debug] [table,table2,table3,....]"
    exit
fi
table=$2

BACKUP_DIR=/home/admin/backup
BZIP2=/usr/bin/bzip2
DATE=`/bin/date +%Y%m%d.%H%M`

if [ ! -d $BACKUP_DIR ]; then
    echo "No backup directory: $BACKUP_DIR"
    echo "--> please exec: mkdir $BACKUP_DIR"
    exit
fi

# ダンプ
ENV=`php ./dbdump.php $target $table`
echo $ENV| grep 'Usage'
if [ $? = 0 ]; then
    # NO ARGUMENT ERROR MESSAGE DISPLAIED
    echo ""
    exit
fi
eval $ENV
if [ "$DBTYPE" = "pgsql" ]; then
    # DROP TABLE を付け足してダンプファイルを生成
    $CMD | perl -npe 's/CREATE TABLE (.+)\(/DROP TABLE $1;\nCREATE TABLE $1 \(\n/' > $BACKUP_DIR/$target.$DATE.dump
else
    $CMD 
fi

# 圧縮
$BZIP2 -9 $BACKUP_DIR/$target.$DATE.dump
