<?php
/**
 * Http Utility
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
class Ao_Util_Http
{
    static public function get($url, $basic_auth_id = null, $basic_auth_pw = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);            //取得するURLを設定
        curl_setopt($ch, CURLOPT_HEADER, false );       //ヘッダーは出力しない
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //curl_exec() の返り値を文字列で返す設定
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);         //CURL 関数の実行にかけられる時間の最大値(秒)
        if($basic_auth_id != "" && $basic_auth_pw != ""){
            curl_setopt($ch, CURLOPT_USERPWD, $basic_auth_id. ":" . $basic_auth_pw);
        }
        // ”The document has moved here" というメッセージがでて処理が止まるので
        // 以下２行をつけたして 302 でリダイレクトした先もアクセスさせる
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        $html = curl_exec($ch);
        curl_close($ch);
        Zend_Registry::get("logger")->debug(__METHOD__.": access by curl ->".$url);
    }
    static public function post($url, $params, $referer_url = null, $basic_auth_id = null, $basic_auth_pw = null)
    {
        $cookie_file = dirname(__FILE__)."/cookie.txt";
        $error_file  = dirname(__FILE__)."/error";

        $param = "";
        foreach($params as $key => $val){
            $param .= "&".$key."=".$val;
        }

        if(file_exists($cookie_file)){
            $fp = fopen($cookie_file, "a");
        } else {
            $fp = fopen($cookie_file, "w");
        }
        $err_fp = fopen($error_file, "w");

        $ch = curl_init($url);

        // リダイレクト先があれば飛ぶ（これないとまたログイン画面に戻る）
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_WRITEHEADER, $fp);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
        //curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $err_fp);
        if( $referer_url ){
            curl_setopt($ch, CURLOPT_REFERER, $referer_url);
        }

        // サイトへアクセス
        $result = curl_exec($ch);

        // HTTPステータスコードをチェックしエラーならエラー内容を出力
        if(curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        // セッションをクローズ
        curl_close($ch);
        fclose($fp);
        fclose($err_fp);

        // リクエストの内容を出力
        return preg_replace("/<br \/>\n/", "\n", htmlspecialchars_decode($result));
    }
}
