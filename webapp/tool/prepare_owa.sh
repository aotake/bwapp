#!/bin/sh
#
# prepare owa
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

# アクセス解析システム Open Web Analytics のインストール準備スクリプト
#
# このスクリプトを実行後、 Web 画面からインストール処理をする
# インストール後表示される画面の SiteId を webapp/config/custom/config.ini で設定する
#   --> 未設定ならアクセス解析処理のプラグインはスキップされる

ARCHIVE=owa_1_5_0rc3.tar

# パッケージを展開する
pushd ../../html/ > /dev/null
if [ -d ./owa ]; then
    echo "--> owa archive is already extracted."
else
    if [ -f ../extra/$ARCHIVE ]; then
        tar xvf ../extra/$ARCHIVE
    else
        echo "====> Not found : ../extra/$ARCHIVE"
        exit
    fi
fi

# install 時に magic_quote_gpc が off じゃないとこける
if [ ! -f ./owa/.htaccess ]; then
    echo "--> put owa/.htaccess"
    echo 'php_value "magic_quotes_gpc" off' > owa/.htaccess
else
    echo "--> exist owa/.htaccess"
fi

# 1.5.0rc3 で残っているバグ対応(for PHP 5.3 以上)
grep "&new owa_php" owa/index.php  > /dev/null
if [ $? = 0 ]; then
    echo "----> convert from '&new owa_php' to 'new owa_php'"
    sed "s/&new owa_php/new owa_php/" owa/index.php > tmp
    mv tmp owa/index.php
fi

# config が書き換わっていたらインストール済みと判定して終了する
if [ -f owa/owa-config.php ]; then
    diff owa/owa-config-dist.php owa/owa-config.php > /dev/null
    if [ $? = 1 ]; then
        echo "----> Already installed"
        exit
    fi
else
    popd >& /dev/null
    #echo $(cd $(dirname $0);pwd)
    WEBAPP_DIR=$(cd ../;pwd) # 一つ上の webapp の絶対パス
    cat ./skel/owa-config.php | sed -e "s#<WEBAPP_DIR>#${WEBAPP_DIR}#" > ../../html/owa/owa-config.php
    echo "----> COPY owa-config.php from skelton."
fi

# まだインストールしていなければ tool に戻って DB 情報を知らせる
popd >& /dev/null
php ./show_owa_dbconf.php
