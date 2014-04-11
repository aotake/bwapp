#/bin/sh
#
# make cloneproject.csv
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
# このスクリプト以下にある GIT プロジェクトにたいして一括操作をする
#
TARGET_DIR=../../
GIT_RESOURCES=`find $TARGET_DIR -name .git -type d -exec dirname {} \;`
GIT_SERVER=$1
CSV=../config/custom/cloneproject.csv
ECHO=/bin/echo

if [ -f tmp ]; then
    rm -f tmp
else
    touch tmp
fi

for d in $GIT_RESOURCES; do
    if [ "$d/" = "${TARGET_DIR}" ]; then
        continue;
    fi
    $ECHO "[CHECK]=======> $d"
    MOD=`basename $d`
    URL=`cat $d/.git/config | grep url | sed "s/url = \(.*\)/\1/" | awk '{print $1}'`
    if [ "$URL" = "" ]; then
        $ECHO "====> no url: ".$d
        continue
    fi

    $ECHO $URL | grep "templates_custom" > /dev/null
    if [ $? = 0 ]; then
        TYPE=templates_custom
    else
        $ECHO $URL | grep "/config" > /dev/null
        if [ $? = 0 ]; then
            $ECHO $URL | grep "/modules" > /dev/null
            if [ $? = 0 ]; then
                TYPE=module_config
            else
                TYPE=webapp_config
            fi
        else
            $ECHO $URL | grep "bmathlog_custom" > /dev/null
            if [ $? = 0 ]; then
                $ECHO $URL | grep "/controller" > /dev/null
                if [ $? = 0 ]; then
                    TYPE=custom_controller
                else 
                    $ECHO $URL | grep "/libs" > /dev/null
                    if [ $? = 0 ]; then
                        TYPE=custom_libs
                    else
                        $ECHO $URL | grep "/layout" > /dev/null
                        if [ $? = 0 ]; then
                            TYPE=layout
                        else
                            $ECHO $URL | grep "/css" > /dev/null
                            if [ $? = 0 ]; then
                                TYPE=custom_css
                            else
                                TYPE=module
                            fi
                        fi
                    fi
                fi
            else
                $ECHO $URL | grep "bmathlog_.*module" > /dev/null
                if [ $? = 0 ]; then
                    TYPE=module
                else
                    TYPE=layout
                fi
            fi
        fi
    fi
    if [ "$GIT_SERVER" ]; then
        $ECHO "$TYPE, $GIT_SERVER:$URL, $MOD" >> tmp
    else
        $ECHO "$TYPE, $URL, $MOD" >> tmp
    fi
done
#cat tmp | sort > tmp.tmp
#mv tmp.tmp tmp
cat tmp
echo -------

# 苦肉のソートもどき
TMP=tmp.tmp
cp /dev/null $TMP
cat tmp | grep "^webapp_config," | sort >> $TMP
cat tmp | grep "^layout," | sort >> $TMP
cat tmp | grep "^module," | sort >> $TMP
cat tmp | grep "^module_config," | sort >> $TMP
cat tmp | grep "^templates_custom," | sort >> $TMP
cat tmp | grep "^custom_controller," | sort >> $TMP
cat tmp | grep "^custom_libs," | sort >> $TMP
cat tmp | grep "^custom_css," | sort >> $TMP
mv $TMP tmp
cat tmp

$ECHO -n "copy to cloneproject.csv [y/(n)]? "
read ans
if [ "$ans" != "y" ]; then
    $ECHO "done without saving."
    rm tmp
    exit
fi

if [ -f $CSV ]; then
    if [ -f $CSV.bak ];then
        rm -f $CSV.bak
    fi
    cp $CSV $CSV.bak
fi
cp tmp $CSV
if [ $? = 0 ]; then
    $ECHO "save : $CSV"
    $ECHO "done."

    rm tmp
else
    $ECHO "failed: save cloneproject.csv to ../config/custom/"
    $ECHO "---> save cloneproject.csv to ./custom/ instead of above one."
    mv  tmp cloneproject.csv
fi
