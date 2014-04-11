<?php
/**
 * Bentchmark Utility
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
 * @author        aotake <aotake@bmath.org>
 */

/*
 * PHP の処理時間を計測する
 *
 * @category Util
 * @package Util_Ao
 * @author aotake <aotake@bmath.org>
 */
class Ao_Util_Benchmark
{
    var $key;
    var $score_repo;
    var $_bench_enabled;

    function __construct( $key = "default" )
    {
        $this->key = $key;
        $this->score_repo = array();
        $this->init( $key );
    }

    function init( $key = "default" )
    {
        $this->score_repo[ $key ]['_start'] = 0;
        $this->score_repo[ $key ]['_end']   = 0;

        //if( defined("USE_BENCHMARK") ){
        //    $this->_bench_enabled = USE_BENCHMARK;
        //} else {
        //    $this->_bench_enabled = false;
        //}
        $this->_bench_enabled = true;
    }

    function start($key = null)
    {
        if( !$this->_bench_enabled ){
            return ;
        }

        if ( !$key ){
            $key = $this->key;
        }
        $this->score_repo[ $key ]['_start'] = microtime(true);
    }
    function initStart($key)
    {
        $this->init($key);
        $this->start($key);
    }
    function stop($key = null)
    {
        if( !$this->_bench_enabled ){
            return ;
        }

        if ( !$key ){
            $key = $this->key;
        }
        $this->score_repo[ $key ]['_end'] = microtime(true);
    }
    function score($key = null, $flag = null )
    {
        if( !$this->_bench_enabled ){
            return ;
        }

        if ( !$key ){
            $key = $this->key;
        }
        $_score = $this->score_repo[ $key ]['_end'] - $this->score_repo[ $key ]['_start'];
        return $_score;
    }

    function getRepo()
    {
        return $this->score_repo;
    }

    function repo()
    {
        foreach($this->score_repo as $key => $data){
            $this->score_repo[$key]["score"] = $data["_end"] - $data["_start"];
        }
        print_r($this->score_repo);
    }

    function log_score($key = null, $flag = null)
    {
        if( !$this->_bench_enabled ){
            return ;
        }

        $score = $this->score($key, $flag);
        error_log("[".date("Y-m-d H:i:s")."] benchmark, ".CALL_SCRIPT_NAME.", ".$_SERVER['REMOTE_ADDR'].", ".$score."\n", 3, BNCFILE);
    }
}
