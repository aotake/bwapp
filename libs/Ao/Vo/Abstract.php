<?php

/**
 * Vo
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
 * @package       Ao.Vo
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Ao_Vo_Abstract {
    private $_attr;
    public function set($key = null, $val = null)
    {
        $this->_attr[$key] = $val;
    }
    public function get($key = null, $default = null)
    {
        if(!$key){
            throw new Zend_Exception("No key was given");
        }
        if(!$this->_attr){
            throw new Zend_Exception("no attribute, class=".get_class($this).", key = ".$key);
        }
        if(array_key_exists($key, $this->_attr)){
            return $this->_attr[$key];
        } else {
            return $default;
        }
    }
    public function toArray()
    {
        return $this->_attr;
    }
}
