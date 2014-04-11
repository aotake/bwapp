<?php
/**
 * Smarty wrapper
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

/*
 * ファイルアップロードツール
 *
 * ■ 保存ファイルの書式
 * md5($_FILES[$upfield_key]['name'][$num]) . ".".$timestamp.".".$extension;
 *
 *
 *
 */
class Ao_Util_Uploader {

	var $uploaddir;
	var $allow_extensions;
	var $extension_flag;
	var $uploadfile;
	var $up_origname;
	var $up_timestamp;
    var $up_mimetype;
    var $error_code;
    var $error_code2;

    var $logger;

	function UploadTool( $upload_dir = null ){
		$this->__construct( $upload_dir );
	}

	function __construct( $uploaddir = null, $allow_exts = array() ){
        $registry = Zend_Registry::getInstance();
        $this->logger = $registry["logger"];
		if( $uploaddir != "" && !is_dir( $uploaddir ) ){
			$msg = "UploadTool::__construct(): Not found Upload directory: $upload_dir\n";
			print $msg;
			$this->logger->debug($msg);
			exit;
		}

		$this->uploaddir = trim($uploaddir);
		$this->allow_extensions = $allow_exts;

		$this->extension_flag = false;
		$this->uploadfile   = null;
		$this->up_origname  = null;
		$this->up_timestamp = null;

        $this->error_code   = null;

	}

	function validExtension($filepath){
		if( empty($this->allow_extensions) ){
			$msg = "UploadTool::validExtension(): No allow file extensions array\n";
			//print $msg;
			$this->logger->debug($msg);
            return false;
			//exit;
		}

		foreach( $this->allow_extensions as $ext ){
			$_extension = substr($filepath, - strlen($ext));
			if (strtolower($_extension) === strtolower($ext)) {
				$this->extension_flag = true;
				return true;
			}
		}
		return false;
	}

	function extensionFlag(){
		return $this->extension_flag;
	}

    function existUploadFile($form_no = 0, $upfield_key = "userdata")
    {
        // そもそもセットされていなければ false
        if(!isset($_FILES[$upfield_key]["error"][$form_no])){
            return false;
        }

        return ($_FILES[$upfield_key]["error"][$form_no] != UPLOAD_ERR_NO_FILE);
    }

    /**
     *
     * アップロードされたファイルで OK ステータスの画像の Index を求める 
     */
    function uploadFileIndex($upfield_key = "userdata")
    {
        if(!isset($_FILES[$upfield_key])){
            return 0;
        }
        $idx = array();
        foreach($_FILES[$upfield_key]["error"] as $i => $item)
        {
            if($item == UPLOAD_ERR_OK){
                $idx[] = $i;
            }
        }
        return $idx;
    }

	function upload($uploaddir = null, $allow_exts = null, $upfield_key = 'userdata', $num = null){
        $date = date("Y-m-d H:i:s");

		if( $uploaddir ){
			$this->uploaddir = $uploaddir;
		}
		if( $allow_exts && is_array( $allow_exts ) ){
			$this->allow_extensions = $allow_exts;
		}

        if(!isset($_FILES[$upfield_key])){
            $msg = "UploadTool::upload() - no upload file form: $upfield_key";
            $this->logger->debug("[$date] $upfield_key, $msg");
            return false;
        }

        switch( $_FILES[$upfield_key]['error'][$num]){
        case UPLOAD_ERR_INI_SIZE:
            $msg = "UploadTool::upload() - UPLOAD_ERR_INI_SIZE error";
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $msg = "UploadTool::upload() - UPLOAD_ERR_FORM_SIZE error";
            break;
        case UPLOAD_ERR_PARTIAL:
            $msg = "UploadTool::upload() - UPLOAD_ERR_PARTIAL error";
            break;
        case UPLOAD_ERR_NO_FILE:
            $msg = "UploadTool::upload() - UPLOAD_ERR_NO_FILE error";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $msg = "UploadTool::upload() - UPLOAD_ERR_NO_TMP_DIR error";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $msg = "UploadTool::upload() - UPLOAD_ERR_CANT_WRITE error";
            break;
/*
        case UPLOAD_ERR_EXTENSION:
            $msg = "UploadTool::upload() - UPLOAD_ERR_EXTENSION error";
            break;
*/
        case UPLOAD_ERR_OK:
        default:
            $msg = null;
            break;
        }
        $this->error_code = $_FILES[$upfield_key]['error'][$num];
        if( $msg ){
            $this->logger->debug("[$date] $upfield_key - $num, $msg");
            return false;
        }

		$timestamp    = date("Ymd_His");
        $extension    = pathinfo(strtolower($_FILES[$upfield_key]['name'][$num]), PATHINFO_EXTENSION);
		$this->up_timestamp = date("Y-m-d H:i:s");
		$this->uploadfile   = md5($_FILES[$upfield_key]['name'][$num]) . ".".$timestamp.".".$extension;
		$this->up_origname  = $_FILES[$upfield_key]['name'][$num];
        $this->up_mimetype  = $_FILES[$upfield_key]['type'][$num];
        $this->up_filesize  = $_FILES[$upfield_key]['size'][$num];
        $up_origname_enc    = mb_detect_encoding($this->up_origname, "UTF8,EUC,SJIS");
        if( $up_origname_enc != "UTF-8" ){
            $this->up_origname = mb_convert_encoding( $this->up_origname, "UTF-8", $up_origname_enc);
        }
		$filepath = $this->uploaddir."/".$this->uploadfile;

		if( !is_readable($_FILES[$upfield_key]['tmp_name'][$num]) ){
            $this->error_code = 101;
			$msg = "upload(): Error - tmporary file not readable\n";
            $this->logger->debug("[$date] $upfield_key($num), $msg");
			return false;
		}
		if ( !$this->validExtension($filepath) ){
            $this->error_code = 102;
			$msg = "upload(): Error - Invalid Extension";
            $this->logger->debug("[$date] $upfield_key($num), $msg");
			return false;
		}
		if (!is_uploaded_file($_FILES[$upfield_key]['tmp_name'][$num])){
            $this->error_code = 103;
			$msg = "ERROR: Don't move tmp file to ".$this->uploaddir;
            $this->logger->debug("[$date] $upfield_key($num), $msg");
			return false;
		}
		if (!move_uploaded_file($_FILES[$upfield_key]['tmp_name'][$num], $filepath)) {
            $this->error_code = 104;
			$msg = "ERROR: Upload Error";
            $this->logger->debug("[$date] $upfield_key($num), $msg");
			return false;
		}
        if(defined("UPLOADTOOL_CHMOD")){
            chmod($filepath, UPLOADTOOL_CHMOD);
        }
		return true;
	}

	function uploadTimestamp(){
		return $this->up_timestamp;
	}

	/*
	 * アップロードがされているか
	 *	- アップロード前なら null が、
	 *	- アップロード後ならファイル名がある
	 */
	function uploaded(){
		return !empty($this->uploadfile) && file_exists($this->uploaddir."/".$this->uploadfile);
	}

	function uploaddir(){
		return $this->uploaddir;
	}

	function uploadfile(){
		return $this->uploadfile;
	}

	function saveFilename(){
		return $this->uploaddir."/".$this->uploadfile;
	}

    function renameTo($new_name = null)
    {
        if(!$new_name){
            return false;
        }
        $cur = $this->uploaddir."/".$this->uploadfile;
        $new = $this->uploaddir."/".$new_name;
        if(!rename($cur, $new)){
            return false;
        }

        // 念のため過去ファイルがあるかチェックして、あれば削除
        if(file_exists($cur)){
            unlink($cur);
        }
        return true;
    }

	function originalFilename(){
        // NOTE: upOrigname を使う（廃止予定関数）
		return $this->up_origname;
	}
    function upOrigname()
    {
        return $this->up_origname;
    }

    function upMimeType(){
        return $this->up_mimetype;
    }
    function upFilesize(){
        return $this->up_filesize;
    }

    public function errorCode()
    {
        return $this->error_code;
    }
}

