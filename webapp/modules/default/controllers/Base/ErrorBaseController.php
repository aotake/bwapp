<?php
/**
 * Default Error Base Controller
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
class ErrorBaseController extends Ao_Controller_AclAction
{
    public function errorAction()
    {
        $errors = $this->_getParam("error_handler");
        $e = $errors->exception;
        switch($errors->type){
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 エラー -- コントローラあるいはアクションが見つかりません
                $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
                $content = "ページは存在しません。";
                break;
            default:
                // アプリケーションのエラー
                $content = "予期せぬエラーが発生しました。後でもう一度お試しください。";
                break;
        }

        // 前回の内容を消去します
        $this->getResponse()->clearBody();

        $log = "addr=".$_SERVER["REMOTE_ADDR"].", msg=".$e->getMessage()
                .", uri=".$_SERVER["REQUEST_URI"]
                .", ua=".$_SERVER["HTTP_USER_AGENT"];

        $sess_auth = new Zend_Session_Namespace("auth");
        if(isset($sess_auth->user_id)){
            $log .= ", uid=".$sess_auth->user_id;
        }
        if(isset($sess_auth->role)){
            $log .= ", role=".$sess_auth->role;
        }

        Zend_Registry::get("logger")->err($log);
        $this->view->content = $content;

        $this->view->message = $e->getMessage();
        $this->view->code = $e->getCode();
        $this->view->file = $e->getFile();
        $this->view->line = $e->getLine();
        $this->view->tostring = $e->__toString();
        $this->view->trace_str= $e->getTraceAsString();
        //$this->view->trace = $e->getTrace();
        $trace = $e->getTrace();
        foreach($trace as $i => $items){
            foreach($items["args"] as $j => $arg){
                $v = null;
                if(is_array($arg)){
                    ob_start();
                    print_r($arg);
                    $content = ob_get_contents();
                    ob_end_clean();
                    $v = $content;
                } else if (is_string($arg)){
                    $v = $arg;
                } else if (is_object($arg)){
                    $v = get_class($arg);
                }
                $items["args"][$j] = $v;
            }
            $trace[$i] = $items;
        }
        $this->view->trace = $trace;

        $res = $this->_varToString();
        $this->view->_server = $res["_server"];
        $this->view->_session = $res["_session"];
        $this->view->_request = $res["_request"];
        $this->view->_post = $res["_post"];
        $this->view->_get = $res["_get"];
        $this->view->_files = $res["_files"];
        $this->view->_cookie = $res["_cookie"];

        // エラーが出たモジュールのテンプレート場所からこのモジュールのテンプレート場所に切り替える
        $webappdir = Zend_Registry::get("webappDir");
        $this->view->setScriptPath($webappdir."/modules/default/templates");
    }
    private function _varToString()
    {
        $res = array();
        ob_start();var_dump($_SERVER);$c=ob_get_contents();ob_end_clean();
        $res["_server"] = $c;

        ob_start();var_dump($_SESSION);$c=ob_get_contents();ob_end_clean();
        $res["_session"] = $c;

        ob_start();var_dump($_REQUEST);$c=ob_get_contents();ob_end_clean();
        $res["_request"] = $c;

        ob_start();var_dump($_POST);$c=ob_get_contents();ob_end_clean();
        $res["_post"] = $c;

        ob_start();var_dump($_GET);$c=ob_get_contents();ob_end_clean();
        $res["_get"] = $c;

        ob_start();var_dump($_FILES);$c=ob_get_contents();ob_end_clean();
        $res["_files"] = $c;

        ob_start();var_dump($_COOKIE);$c=ob_get_contents();ob_end_clean();
        $res["_cookie"] = $c;

        return $res;
    }
}
