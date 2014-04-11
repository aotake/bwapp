#!/bin/sh
#
# utility for gitcheck.sh
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
# git check で使うファンクション
#

# 以下、ヒットすれば 1, ヒットしなければ 0 が返る

function hasKeyword()
{
    TMP_PATH=$1
    KEYWORD=$2
    grep "$KEYWORD" $TMP_PATH >& /dev/null
    echo $?
    return
}
function repositoryChanged()
{
    TMP_PATH=$1
    grep -E "ahead of|modified|deleted|Untracked" $TMP_PATH >& /dev/null
    echo $?
    return
}
function alreadyUpToDate()
{
    TMP_PATH=$1
    grep "Already up-to-date" $TMP_PATH >& /dev/null
    echo $?
    return
}
function hasFatal()
{
    TMP_PATH=$1
    grep -i "fatal" $TMP_PATH >& /dev/null
    echo $?
    return 
}
