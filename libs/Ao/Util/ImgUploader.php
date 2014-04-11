<?php
/**
 * Imagefile Upload Utility
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
class Ao_Util_ImgUploader extends Ao_Util_Uploader
{
    const CONVERT = "/opt/local/bin/convert";
    const DEFAULT_THUMB_W = 240;
    const DEFAULT_THUMB_H = 240;
    const MOBILE_THUMB_W = 120;
    const MOBILE_THUMB_H = 120;
    const MOBTHUMB_THUMB_W = 75;
    const MOBTHUMB_THUMB_H = 75;

    var $_registry;
    var $_thumb_w;
    var $_thumb_h;
    var $_mobile_w;
    var $_mobile_h;
    var $_mobthumb_w;
    var $_mobthumb_h;

    var $_fit = false;

    private $convert_cmd;

    public function __construct()
    {
        $this->_registry = Zend_Registry::getInstance();
        $this->_thumb_w = self::DEFAULT_THUMB_W;
        $this->_thumb_h = self::DEFAULT_THUMB_H;
        $this->_mobile_w = self::MOBILE_THUMB_W;
        $this->_mobile_h = self::MOBILE_THUMB_H;
        $this->_mobthumb_w = self::MOBTHUMB_THUMB_W;
        $this->_mobthumb_h = self::MOBTHUMB_THUMB_H;
        $this->setupConvertCmd();
        parent::__construct();
    }

    public function setupConvertCmd()
    {   
        $front = Zend_Controller_Front::getInstance();
        $modname = $front->getRequest()->getModuleName();
        $reg = Zend_Registry::getInstance();
        $cmd_path = self::CONVERT;
        if(isset($reg["config"]->upload->convert_cmd)){
            $cmd_path = $reg["config"]->upload->convert_cmd;
        }
        if(isset($reg["modconf"]->$modname->upload->convert_cmd)){
            $cmd_path = $reg["modconf"]->$modname->upload->convert_cmd;
        }
        if(!file_exists($cmd_path)){
            throw new Zend_Exception("not found: $cmd_path");
        }
        $this->convert_cmd = $cmd_path;
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
    public function  mkMobthumbImg($name = null) // モバイル用サムネイル？
    {
        $w = $this->_mobthumb_w;
        $h = $this->_mobthumb_h;
        return $this->_mkThumb($name, "mobthumb", $w, $h);
    }
    private function _mkThumb($name = null, $type = "thumb", $width, $height)
    {
        $filepath = $this->uploaddir."/".$name;
        $info = getimagesize($filepath);

        $thumbpath = $filepath."_".$type;

        // 横幅のみ指定ピクセルで変更し、縦幅はアスペクト比を維持して変更する
        if($height == 0 && $info[0] > $width) {
            $cmd = $this->convert_cmd." -resize ".$width
                    ." ".$filepath." ".$thumbpath;
            if(file_exists($filepath)){
                $res = shell_exec($cmd);
            } else {
                $registry = Zend_Registry::getInstance();
                $logger = $registry["logger"];
                $logger->debug(__METHOD__.", no exists $name");
            }
        }
        // アスペクト比を維持して width x height の枠に収まるように変更する
        else if($info[0] > $width || ($info[1] > $height && $height != 0)) {
            //$cmd = self::CONVERT." -resize ".$width."x".$height
            $cmd = $this->convert_cmd." -resize ".$width."x".$height
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

    /*
     * resizeByWidth()
     *
     * 横幅のみ調整し縦幅はアスペクト比を維持したまま縮小したいとき
     * 縦幅のメンバー変数を 0 にする
     */
    public function resizeByWidth()
    {
        $this->_thumb_h = 0;
        $this->_mobile_h = 0;
        $this->_mobthumb_h = 0;
    }
}

