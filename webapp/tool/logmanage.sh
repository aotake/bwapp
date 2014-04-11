#!/bin/sh
#
# 30 日以上たったログファイルは削除し、30 日以内であれば bzip2 で圧縮
#
BZIP2=/usr/bin/bzip2
FIND=/bin/find
LOGDIR=/home/web/production/webapp/log/
TODAY=$(/bin/date "+%Y%m%d")

echo "--> check old file and remove"
$FIND $LOGDIR -mtime +30 -name "20*.log" -exec rm -f {} \;
$FIND $LOGDIR -mtime +30 -name "20*.log.bz2" -exec rm -f {} \;

echo "--> check no compress logfile and compress"
for f in `$FIND $LOGDIR -name "20*.log" -print | grep -v $TODAY.log`; do
    echo "----> $f"
    $BZIP2 -9 $f
    echo "...done"
done
