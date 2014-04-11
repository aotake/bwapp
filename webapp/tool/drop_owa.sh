#!/bin/sh
#
# drop owa tables script
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

tables="
owa_action_fact
owa_ad_dim
owa_campaign_dim
owa_click
owa_commerce_line_item_fact
owa_commerce_transaction_fact
owa_configuration
owa_document
owa_domstream
owa_exit
owa_feed_request
owa_host
owa_impression
owa_location_dim
owa_os
owa_queue_item
owa_referer
owa_request
owa_search_term_dim
owa_session
owa_site
owa_source_dim
owa_ua
owa_user
owa_visitor
"

if [ -f tmp ]; then
    rm tmp
fi
touch tmp
for t in $tables; do
    echo "drop table if exists $t;" >> tmp
done

cat tmp | /bin/sh ./console.sh
rm tmp
