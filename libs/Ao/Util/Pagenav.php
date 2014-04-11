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
class Ao_Util_Pagenav
{
    var $total;
    var $perpage;
    var $current;
    var $url;

    /**
     * Constructor
     *
     * @param   int     $total_items    Total number of items
     * @param   int     $items_perpage  Number of items per page
     * @param   int     $current_start  First item on the current page
     * @param   string  $start_name     Name for "start" or "offset"
     * @param   string  $extra_arg      Additional arguments to pass in the URL
     **/
    public function __construct(
        $total_items=null, $items_perpage=null, $current_start=null, 
        $start_name="offset", $extra_arg="", $base_url = null
    ){
        if($total_items){
            $this->init($total_items, $items_perpage,
                    $current_start, $start_name, $extra_arg, $base_url);
        }
    }

    protected function init(
        $total_items, $items_perpage, $current_start,
        $start_name="offset", $extra_arg="", $base_url
    ){
        $this->total = intval($total_items);
        $this->perpage = intval($items_perpage);
        $this->current = intval($current_start);
        if($extra_arg != ''
            && (substr($extra_arg, -5) != '&amp;'
                || substr($extra_arg, -1) != '&' )
        ){
            $extra_arg .= '&amp;';
        }
        if( !$base_url ){
            $info = parse_url($_SERVER["REQUEST_URI"]);
            $base_url = $info["path"];
        }
        $this->url = $base_url.'?'.$extra_arg.trim($start_name).'=';
    }

    public function set($key, $val)
    {
        $this->{$key} = $val;
    }

    /**
     * Create text navigation
     *
     * @param   integer $offset
     * @return  string
     **/
    function renderNav($offset = 4)
    // {{{
    {
        $ret = '';
        if ( $this->total <= $this->perpage ) {
            return $ret;
        }
        $total_pages = ceil($this->total / $this->perpage);
        if ( $total_pages > 1 ) {
            $prev = $this->current - $this->perpage;
            if ( $prev >= 0 ) {
                $ret .= '<a class="navlink" href="'.$this->url.$prev.'"><u>&lt;&lt;</u></a> ';
            } else{
                $ret .= '<u>&lt;&lt;</u> ';
            }
            $counter = 1;
            $current_page = intval(floor(($this->current + $this->perpage) / $this->perpage));
            while ( $counter <= $total_pages ) {
                if ( $counter == $current_page ) {
                    $ret .= '<b>('.$counter.')</b> ';
                } elseif ( ($counter > $current_page-$offset && $counter < $current_page + $offset ) || $counter == 1 || $counter == $total_pages ) {
                    if ( $counter == $total_pages && $current_page < $total_pages - $offset ) {
                        $ret .= '... ';
                    }
                    $ret .= '<a class="navlink" href="'.$this->url.(($counter - 1) * $this->perpage).'">'.$counter.'</a> ';
                    if ( $counter == 1 && $current_page > 1 + $offset ) {
                        $ret .= '... ';
                    }
                }
                $counter++;
            }
            $next = $this->current + $this->perpage;
            if ( $this->total > $next ) {
                $ret .= '<a class="navlink" href="'.$this->url.$next.'"><u>&gt;&gt;</u></a> ';
            } else{
                $ret .= '<u>&gt;&gt;</u> ';
            }
        }
        return $ret;
    }
    // }}}
    /**
     * Create text navigation
     *
     * @param   integer $offset
     * @return  string
     * [sample CSS]
     * .PagingBox { clear:both;}
     * .PagingArea { clear:both; text-align:center;}
     * .PagingArea li { display:inline-block; *display:inline; *zoom:1; line-height:25px; height:25px; border-top:1px solid #e2c892; border-left:1px solid #e2c892; border-bottom:1px solid #e2c892; color:#3A5998; vertical-align:middle; background:#F7EBD2; color:#999; font-weight:bold;}
     * .PagingArea li .CS1 { display:inline-block; *display:inline; *zoom:1; line-height:23px; height:23px; padding:0 10px 0; border:1px solid #fff;}
     * .PagingArea li .CS2 { display:inline-block; *display:inline; *zoom:1; line-height:25px; height:25px; color:#999; background:#fff;}
     * .PagingArea li a { color:#333; text-decoration:none; cursor:pointer;}
     * .PagingArea li a:hover {}
     * .PagingArea li.Prev { border-left:0; border-top:0; border-bottom:0; background:#c00 url(../img/base/BgPaging1.jpg) 0 0 no-repeat; line-height:27px; height:27px; width:63px;}
     * .PagingArea li.Next { border-top:0; border-bottom:0; background:url(../img/base/BgPaging2.jpg) 0 0 no-repeat; line-height:27px; height:27px; width:66px;}
     * .PagingArea li.Next a { text-decoration:none;}
     * .PagingArea li.Next a .CS3 { color:#9E6E10;}
     * [/sample CSS]
     **/
    function renderNavVisual($offset = 4)
    // {{{
    {
        $ret = '';
        if ( $this->total <= $this->perpage ) {
            return $ret;
        }
        $ret .= '<div class="PagingBox">';
        $ret .= '<ul class="PagingArea APkg">';
        $total_pages = ceil($this->total / $this->perpage);
        if ( $total_pages > 1 ) {
            $prev = $this->current - $this->perpage;
            if ( $prev >= 0 ) {
                $ret .= '<li class="Prev"><a href="'.$this->url.$prev.'">&lt;&lt; <span>前へ</span></a></li>';
            } else{
                $ret .= '<li class="Prev">&lt;&lt; <span>前へ</span></li>';
            }
            $counter = 1;
            $current_page = intval(floor(($this->current + $this->perpage) / $this->perpage));
            while ( $counter <= $total_pages ) {
                if ( $counter == $current_page ) {
                    $ret .= '<li><span class="CS2"><span class="CS1">'.$counter.'</span></span></li>';
                } elseif ( ($counter > $current_page-$offset && $counter < $current_page + $offset ) || $counter == 1 || $counter == $total_pages ) {
                    if ( $counter == $total_pages && $current_page < $total_pages - $offset ) {
                        $ret .= '<li><span class="CS1">...</span></li>';
                    }
                    $ret .= '<li><a href="'.$this->url.(($counter - 1) * $this->perpage).'"><span class="CS1">'.$counter.'</span></a></li>';
                    if ( $counter == 1 && $current_page > 1 + $offset ) {
                        $ret .= '<li><span class="CS1">...</span></li>';
                    }
                }
                $counter++;
            }
            $next = $this->current + $this->perpage;
            if ( $this->total > $next ) {
                $ret .= '<li class="Next"><a href="'.$this->url.$next.'">次へ <span class="CS3">&gt;&gt;</a></li>';
            } else{
                $ret .= '<li class="Next"><u>&gt;&gt;</u></li>';
            }
        }
        $ret .= '</ul>';
        $ret .= '</div>';
        return $ret;
    }
    // }}}
    /**
     *
     * [sample CSS]
     * [/sample CSS]
     */
    function renderNavVisual2($offset = 4)
    // {{{
    {
        $ret = '';
        if ( $this->total <= $this->perpage ) {
            return $ret;
        }
        //$ret .= '<div class="SDPaging1">';
        $ret .= '<ul class="SUPaging1">';
        $total_pages = ceil($this->total / $this->perpage);
        if ( $total_pages > 1 ) {
            $prev = $this->current - $this->perpage;
            if ( $prev >= 0 ) {
                $ret .= '<li class="CLiPrev1"><a href="'.$this->url.$prev.'">前へ</a></li>';
            } else{
                $ret .= '<li class="CLiPrev1"><a href="#">前へ</a></li>';
            }
            $counter = 1;
            $current_page = intval(floor(($this->current + $this->perpage) / $this->perpage));
            while ( $counter <= $total_pages ) {
                if ( $counter == $current_page ) {
                    $ret .= '<li><span class="ROn">'.$counter.'</span></li>';
                } elseif ( ($counter > $current_page-$offset && $counter < $current_page + $offset ) || $counter == 1 || $counter == $total_pages ) {
                    if ( $counter == $total_pages && $current_page < $total_pages - $offset ) {
                        $ret .= '<li><span>...</span></li>';
                    }
                    $ret .= '<li><a href="'.$this->url.(($counter - 1) * $this->perpage).'">'.$counter.'</a></li>';
                    if ( $counter == 1 && $current_page > 1 + $offset ) {
                        $ret .= '<li><span>...</span></li>';
                    }
                }
                $counter++;
            }
            $next = $this->current + $this->perpage;
            if ( $this->total > $next ) {
                $ret .= '<li class="CLiNext1"><a href="'.$this->url.$next.'">次へ</a></li>';
            } else{
                $ret .= '<li class="CLiNext1">次へ</li>';
            }
        }
        $ret .= '</ul>';
        //$ret .= '</div>';
        return $ret;
    }
    // }}}
    function renderMiniNav($prev_str = null, $next_str = null, $sep = "|", $off_str= true)
    // {{{
    {
        $ret = '';
        if ( $this->total <= $this->perpage ) {
            return $ret;
        }

        $prev_str = ($prev_str == null) ? "前" : trim($prev_str);
        $next_str = ($next_str == null) ? "次" : trim($next_str);

        $total_pages = ceil($this->total / $this->perpage);
        if ( $total_pages > 1 ) {
            $prev = $this->current - $this->perpage;
            if ( $prev >= 0 ) {
                $ret .= '<a href="'.$this->url.$prev.'"><u>'.$prev_str.'</u></a> ';
            }
            else{
                if( $off_str) $ret .= '<u>'.$prev_str.'</u> ';
            }
            $next = $this->current + $this->perpage;
            $ret .= $sep;
            if ( $this->total > $next ) {
                $ret .= '<a href="'.$this->url.$next.'"><u>'.$next_str.'</u></a> ';
            }
            else{
                if( $off_str) $ret .= '<u>'.$next_str.'</u> ';
            }
        }
        return $ret;
    }
    // }}}

    function renderNext($next_str = null, $off_str= true)
    // {{{
    {
        $ret = '';
        if ( $this->total <= $this->perpage ) {
            return $ret;
        }

        $next_str = ($next_str == null) ? "次" : trim($next_str);

        $total_pages = ceil($this->total / $this->perpage);
        if ( $total_pages > 1 ) {
            $prev = $this->current - $this->perpage;
            if ( $prev >= 0 ) {
                $ret .= '<a href="'.$this->url.$prev.'"><u>'.$prev_str.'</u></a> ';
            }
            else{
                if( $off_str) $ret .= '<u>'.$prev_str.'</u> ';
            }
        }
        return $ret;
    }
    // }}}

    function renderPrev($prev_str = null, $off_str= true)
    // {{{
    {
        $ret = '';
        if ( $this->total <= $this->perpage ) {
            return $ret;
        }

        $prev_str = ($prev_str == null) ? "前" : trim($prev_str);

        $total_pages = ceil($this->total / $this->perpage);
        if ( $total_pages > 1 ) {
            $prev = $this->current - $this->perpage;
            if ( $prev >= 0 ) {
                $ret .= '<a href="'.$this->url.$prev.'"><u>'.$prev_str.'</u></a> ';
            }
            else{
                if( $off_str) $ret .= '<u>'.$prev_str.'</u> ';
            }
        }
        return $ret;
    }
    // }}}

}
