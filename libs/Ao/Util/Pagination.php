<?php
/**
 * Feed Utility
 *
 * PHP 5
 *
 * Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright     Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
 * @link          http://bmath.jp Bmath Web Application Platform Project
 * @package       Ao.Util
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class Ao_Util_Pagination
{
    var $total;       // number of search hit items
    var $total_page;  // number of total  page
    var $perpage;     // number of display item per page
    var $current;     // number of current page
    var $extra_arg;   // query_string
    var $url;

    /**
     * Constructor
     *
     * @param   int     $total_items    Total number of items
     * @param   int     $items_perpage  Number of items per page
     * @param   int     $page           Number of page
     * @param   string  $extra_arg      Additional arguments to pass in the URL
     * @param   string  $base_url       base URL
     **/
    public function __construct( $total_items = null, $items_perpage = null, $page = null, $extra_arg="", $base_url = null ){
        if($total_items){
            $this->init($total_items, $items_perpage, $page, $extra_arg, $base_url);
        }
    }

    protected function init( $total_items, $items_perpage, $page, $extra_arg="", $base_url ) // {{{
    {
        $this->total   = intval($total_items);
        $this->perpage = intval($items_perpage);
        $this->current = intval($page);
        $this->total_page = null;
        $this->extra_arg = "?".$extra_arg;
        if($extra_arg != ''
            && (substr($extra_arg, -5) != '&amp;'
                || substr($extra_arg, -1) != '&' )
        ){
            $extra_arg .= '&amp;';
        }
        // $base_url がなければ現在の REQUEST_URI から path を抜き出して代用する
        if( !$base_url ){
            $info = parse_url($_SERVER["REQUEST_URI"]);
            $base_url = $info["path"];
        }
        //if( $extra_arg ){
        //    $this->url = $base_url.'?'.$extra_arg.trim($start_name).'=';
        //}
        //else {
            $this->url = $base_url;
        //}
    } // }}}
    public function set($key, $val) // {{{
    {
        if( $key == "extra_arg" ){
            $this->{$key} = "?". $val;
        } else {
            $this->{$key} = $val;
        }
    } // }}}

    public function renderNavBootstrap3($nav_offset = 4) // {{{
    {
        $ret = '';

        // 検索マッチ件数が１ページ表示件数以下ならページャは不要
        if ( $this->total <= $this->perpage ) {
            return $ret;
        }

        // ページ数算出
        if( !$this->total_page ){
            $this->total_page = ceil($this->total / $this->perpage);
        }


        // 左端のナビゲーション「<<」のリンク設定
        $prev = $this->current - 1;
        if ( $prev > 1 ) {
            $ret .= '<li><a href="'.$this->url . $prev . '/'.$this->extra_arg.'">&laquo;</a></li> ';
        } else if ( $prev == 1 ) {
            $ret .= '<li><a href="'.$this->url . $this->extra_arg. '">&laquo;</a></li> ';
        } else{
            $ret .= '<li class="disabled"><a href="javascript:void(0);">&laquo;</a></li> ';
        }


        $counter = 1;
        $current_page = $this->current;
        while ( $counter <= $this->total_page ) {

            // カレントページならリンクは数字のみ出力、CSS は active に。
            if ( $counter == $current_page ) {
                $ret .= '<li class="active"><a href="#">'.$counter.'</a></li>';
            }

            // ナビゲーション表示範囲ならリンクを出力
            elseif (
                ( $counter > $current_page - $nav_offset && $counter < $current_page + $nav_offset ) // 表示範囲
                || $counter == 1            // １ページ目
                || $counter == $this->total_page // 最終ページ番号
            ) {

                if ( $counter == $this->total_page && $current_page < $this->total_page - $nav_offset ) {
                    $ret .= '<li class="disabled"><a href="javascript:void(0);">...</a></li>';
                }

                if( $counter == 1 ){ // １ページ目は URL にページ番号を含めない
                    $ret .= '<li><a href="'.$this->url . $this->extra_arg .'">'.$counter.'</a></li>';
                } else {
                    $ret .= '<li><a href="'.$this->url . $counter . '/'.$this->extra_arg.'">'.$counter.'</a></li>';
                }

                if ( $counter == 1 && $current_page > 1 + $nav_offset ) {
                    $ret .= '<li class="disabled"><a href="javascript:void(0);">... </a></li>';
                }
            }
            $counter++;
        }

        // 右端のナビゲーション「>>」の設定
        $next = $this->current + 1;
        if ( $this->total_page >= $next ) {
            $ret .= '<li><a href="'.$this->url . $next . '/'.$this->extra_arg.'">&raquo;</a></li>';
        } else{
            $ret .= '<li class="disabled"><a href="javascript:void(0);">&raquo;</a></li>';
        }

        return $ret;
    }
    // }}}
    public function getNextPageUrl() // {{{
    {
        if( !$this->total_page ){
            $this->total_page = ceil($this->total / $this->perpage);
        }

        $url = null;
        $next = $this->current + 1;
        if ( $this->total_page > $next ) {
            $url = $this->url.$next."/".$this->extra_arg;
        } else {
            $url = "#";
        }
        return $url;

    } // }}}
    public function getPrevPageUrl() // {{{
    {
        $url = null;
        $prev = $this->current - 1;

        if ( $prev <= 0 ) {
            $url = "#";
        }
        else if( $prev == 1 ){
            $url = $this->url;
        }
        else {
            $url = $this->url.$prev."/".$this->extra_arg;
        }

        return $url;
    } // }}}
}
