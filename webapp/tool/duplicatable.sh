#!/bin/sh
#
# convert single module to duplicatable module
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

# シングルモジュールのライブラリ、コントローラの複製対応化と
# 複製対応にともなう Manager,Form,Validator,Model 呼出のコード修正を行う

if [ "$1" = "" ];then
    echo "Usage $0 default_module_name target_module"
    echo
    exit
fi

if [ "$2" != "" ]; then
    mods=$2
else
    echo "Usage $0 default_module_name target_module"
    exit;
fi

/bin/sh ./convertToBaseController.sh $1 $2
/bin/sh ./convertToBaseLib.sh $1 $2
php ./convertNewStatement.php $2

