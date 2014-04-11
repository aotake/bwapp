<?php
/**
 * Default Readimage Base Controller
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
 * @package       Ao.modules.admin.Controller
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author     Takeshi Aoyama (aotake@bmath.org)
 */
class ReadimageBaseController extends Ao_Controller_Action
{
    protected $_logger;
    private $upload_dir;

    public function init()
    {
        parent::init();
        $registry = Zend_Registry::getInstance();
        $this->_logger = $registry["logger"];

        $r = Zend_Registry::getInstance();
        $this->upload_dir = $r["webappDir"]."/uploads";
        //$ldir = $webapp_dir."/modules/default/templates/css";
        //$this->view->setLayoutDir($ldir);
    }
    public function indexAction()
    {
        $this->_putImage();
        exit;
    }
    public function thumbAction()
    {
        $this->_putImage("thumb");
    }
    public function mobileAction()
    {
        $this->_putImage("mobile");
    }
    private function _putImage($_type = null)
    {
        $req = $this->getRequest();
        if($req->getQuery("agent_sign") == ""){
            $this->_logger->debug(__METHOD__.", remote_addr=".$_SERVER["REMOTE_ADDR"].", no agent_sign");
            print "read image error";
            exit;
        }
        if($req->getQuery("estate_no") == ""){
            $this->_logger->debug(__METHOD__.", remote_addr=".$_SERVER["REMOTE_ADDR"].", no estate_no");
            print "read image error";
            exit;
        }
        $agent_sign = trim($_GET['agent_sign']);
        $estate_no  = trim($_GET['estate_no']);
        $img_no     = trim($_GET['img']);
        $is_tmp     = isset($_GET['tmp']) ?trim($_GET['tmp']) : null;
        $image_path = $this->upload_dir;

        $type = $_type != "" ? "_".$_type : "";

        $tmphead    = $is_tmp ? "tmp_" : ""; // edit_estate で confirm のときに使う
        $filename   = $image_path.'/'.$agent_sign.'/'.$tmphead.$estate_no."_".$img_no.$type;
        if( file_exists( $filename ) ){
            $info = getimagesize($filename);
            header("Content-Type: ".$info["mime"]);
            $fp = fopen( $filename, "r" );
            if( !$fp ){
                //error_log("[".date("Y-m-d H:i:s")."] no image, estate_no = $estate_no, img_no = $img_no, ".$_SERVER['REMOTE_ADDR']."\n", 3, LOGFILE);
                //$filename = DOCUMENT_ROOT.'/images/layout/noimage.gif';
                //print fread($fp, filesize($filename));
            }       
            else{
                print fread($fp, filesize($filename));
            }
            fclose( $fp );
        }
        else{
            //$filename = DOCUMENT_ROOT.'/images/layout/spacer.gif';
            //$fp = fopen( $filename, "r" );
            //print fread($fp, filesize($filename));
            //fclose( $fp );
        }
        exit;
    }
}
