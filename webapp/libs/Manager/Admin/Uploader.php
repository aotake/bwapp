<?php
/**
 * Admin Upload Manager
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
 * @package       Ao.webapp.Manager.Admin
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Manager_Admin_Uploader extends Ao_Util_Uploader
{

    const CONVERT = "/opt/local/bin/convert";
    const DEFAULT_THUMB_W = 240;
    const DEFAULT_THUMB_H = 240;
    const MOBILE_THUMB_W = 120;
    const MOBILE_THUMB_H = 120;

    var $_registry;
    var $_thumb_w;
    var $_thumb_h;
    var $_mobile_w;
    var $_mobile_h;

    public function __construct()
    {
        $this->_registry = Zend_Registry::getInstance();
        $this->_thumb_w = self::DEFAULT_THUMB_W;
        $this->_thumb_h = self::DEFAULT_THUMB_H;
        $this->_mobile_w = self::MOBILE_THUMB_W;
        $this->_mobile_h = self::MOBILE_THUMB_H;
        parent::__construct();
    }

    public function existTmpImage($name = null)
    {
        return file_exists($name);
    }

    public function replaceImage($tmp = null, $dist = null)
    {
        rename($tmp, $dist);
    }

    public function  mkThumb($name = null)
    {
        $w = $this->_thumb_w;
        $h = $this->_thumb_h;
        return $this->_mkThumb($name, "thumb", $w, $h);
    }
    public function  mkMobileImg($name = null)
    {
        $w = $this->_mobile_w;
        $h = $this->_mobile_h;
        return $this->_mkThumb($name, "mobile", $w, $h);
    }
    private function _mkThumb($name = null, $type = "thumb", $width, $height)
    {
        $filepath = $this->uploaddir."/".$name;
        $info = getimagesize($filepath);

        $thumbpath = $filepath."_".$type;

        if($info[0] > $width || $info[1] > $height)
        {
            $cmd = self::CONVERT." -resize ".$width."x".$height
                    ." ".$filepath." ".$thumbpath;
            if(file_exists($filepath)){
                $res = shell_exec($cmd);
            } else {
                $registry = Zend_Registry::getInstance();
                $logger = $registry["logger"];
                $logger->debug(__METHOD__.", no exists $name");
            }
            
        } else {
            // サイズがサムネイルサイズより小さければ縮小しない
            copy($filepath, $thumbpath);
        }
    }
}
