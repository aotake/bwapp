<?php
/**
 * PHP Shutdown handler
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
class Ao_Util_PhpShutdownHandler
{
    //
    // Fatal Error を補足する
    //
    // 利用例：
    // register_shutdown_function("Ao_Util_PhpShutdownHandler::execute");
    //
    static function execute()
    {
        $isErr = false;
        if($error = error_get_last()){
            switch($error['type']){
            case E_ERROR:
                $isErr = true; break;
            default:
                break;;
            }
        }
        if($isErr){
            self::errPrint(
                $error["type"],
                $error["message"],
                $error["file"],
                $error["line"],
                null);
        }
    }
    static function errPrint($type, $msg, $file, $line, $context)
    {
        $error_id = md5(time());
        print "Fatal Error: Please contact to site admin.<br>";
        print "Error ID: $error_id";

        $ret[] = "ErrorID: $error_id";
        $ret[] = "ErrorType: $type";
        $ret[] = "Message: $msg";
        $ret[] = "File: $file";
        $ret[] = "Line: $line";
        $ret[] = "Context: $context";

        // 環境情報
        $_sv = $_SERVER;
        unset($_sv["PHP_AUTH_PW"]);
        ob_start();
        print "_SERVER:";
        print_r($_sv);
        print "_REQUEST:";
        print_r($_REQUEST);
        print "_SESSION:";
        print_r($_SESSION);
        $ret[] = "\n".ob_get_contents();
        ob_end_clean();

        // ログファイル
        $error_log = implode(",", $ret);
        Zend_Registry::get("logger")->crit($error_log);

        // メール用メッセージ
        if(Zend_Registry::get("config")->system->admin->email){
            $mail_msg = implode("\n", $ret);
            $emails = explode("|", Zend_Registry::get("config")->system->admin->email);
            foreach($emails as $email){
                mail($email, 'Fatal Error at '.$_SERVER["APPLICATION_ENV"], $mail_msg);
            }
        }
    }
}
