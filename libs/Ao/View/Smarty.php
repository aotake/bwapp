<?php
/**
 * Smarty View
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
 * @package       Ao.View
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Ao_View_Smarty implements Zend_View_Interface
{
    protected $_smarty;
    private $_layout_dir;
    private $_layout_file;

    private $smarty_version;

    protected $__cache_id;
    protected $__fetched_files;
 
    public function __construct($tmplPath = null, $extraParams = array())
    {
        //$this->_smarty = new Smarty();
        $this->_smarty = new Ao_Util_Mysmarty();

        // ver3.x にも対応するためバージョンチェック
        $ver = $this->_smarty->_version;
        $ver = preg_replace("/.*(\d+\.\d+\.\d+)$/", "\\1", $ver);
        $this->smarty_version = $ver;

        if (null !== $tmplPath) {
            $this->setScriptPath($tmplPath);
        }
 
        foreach ($extraParams as $key => $value) {
            $this->_smarty->$key = $value;
        }
        $this->__cache_id = null;
        $this->__fetched_files = array();
    }

    public function getEngine()
    {
        return $this->_smarty;
    }
 
    public function setScriptPath($path)
    {
        if (is_readable($path)) {
            $this->_smarty->template_dir = $path;
            return;
        }
        throw new Exception('無効なパスが指定されました');
    }
 
    public function getScriptPath()
    {
        return $this->_smarty->template_dir;
    }
 
    public function __set($key, $val)
    {
        $this->_smarty->assign($key, $val);
    }
 
    public function __get($key)
    {
        if($this->smarty_version >= "3.0.0"){
            //return $this->_smarty->tpl_vars[$key]; // Smarty3
            if(isset($this->_smarty->tpl_vars[$key])){
                return $this->_smarty->tpl_vars[$key]; // Smarty3
            } else {
                return null;
            }
        } else {
            return $this->_smarty->get_template_vars($key);
        }
    }
 
    public function __isset($key)
    {
        if($this->smarty_version >= "3.0.0"){
            return (null !== $this->_smarty->tpl_vars[$key]); // Smarty3
        } else {
            return (null !== $this->_smarty->get_template_vars($key));
        }
    }
 
    public function __unset($key)
    {
        $this->_smarty->clear_assign($key);
    }
 
    public function assign($spec, $value = null)
    {
        if (is_array($spec)) {
            $this->_smarty->assign($spec);
            return;
        }
        $this->_smarty->assign($spec, $value);
    }
 
    public function assignByRef(&$spec, &$value = null)
    {
        if (is_array($spec)) {
            $this->_smarty->assign_by_ref($spec);
            return;
        }
        $this->_smarty->assign_by_ref($spec, $value);
    }
 
    public function clearVars()
    {
        $this->_smarty->clear_all_assign();
    }
 
    public function fetchStatic($name, $script_path = null)
    {
        if($script_path){
            $this->setScriptPath($script_path);
        }
        return $this->_smarty->fetch($name);
    }
    public function templateCheck( $name, $custom_check = true )
    {
        // カスタムテンプレートチェック

        $dir = $this->getScriptPath();
        // default 環境
        $path = $dir."/".$name;
        // stage 環境
        $stage_dir = $dir."_stage";
        $stage_path = $stage_dir."/".$name;
        if(file_exists($stage_path)){
            $this->setScriptPath($stage_dir);
        }
        // custom 環境
        $custom_dir = $dir."_custom";
        $custom_path = $custom_dir."/".$name;
        if(file_exists($custom_path)){
            $this->setScriptPath($custom_dir);
        }

        // 最終的なスクリプトパス
        $last_dir = $this->getScriptPath();
        if(substr($name, 0, 7) != "string:"){
            // ファイルパスの情報取得
            $_name = pathinfo($name);
            // デモ用ファイルパス
            $_demo_name = $_name["dirname"]."/".$_name["filename"]."_demo.".$_name["extension"];

            // デモモードチェック
            $demo_mode = false;
            if(isset($this->siteconf->value["demo"])){
                $demo_mode = $this->siteconf->value["demo"];
            }
            if($demo_mode == false && isset($this->thismod->value["module"]) && isset($this->thismod->value["module"]["demo"])){
                $demo_mode = $this->thismod->value["module"]["demo"];
            }

            // デモモードでデモ用ファイルがあれば採用
            if($demo_mode == true && file_exists($last_dir."/".$_demo_name)){
                $name = $_demo_name;
            }

            // 言語テンプレートがあればそちらを、なければデフォルト
            // 言語選択は、「日本語 < ブラウザの言語設定 < クッキーに保存された設定」の優先度で採用
            // 言語テンプレートは「ファイル名.言語名.html」の書式であることが前提
            // 言語テンプレートが無い場合「ファイル名.html」が採用される
            $locale = new Zend_Locale();
            $lang = $locale->getLanguage(); // デフォルトはブラウザの言語設定
            $cookie_name = "select_lang"; // 固定値
            if(array_key_exists($cookie_name, $_COOKIE) && $_COOKIE[$cookie_name] != "") {
                $lang = $_COOKIE[$cookie_name];
                $lang_support = Zend_Registry::get("config")->site->language->support;
                // 言語設定があればサポート言語かをチェック
                if($lang_support != "" && !in_array($lang, explode(",",$lang_support))){
                    Zend_Registry::get("logger")->warn("Invalid language was selected: ".$lang);
                    $lang = Zend_Registry::get("config")->site->language->default;
                }
                // 言語設定が無ければ日本語を強制する
                else if($lang_support == ""){
                    $lang = "ja";
                }
            }
            $_lang_path = pathinfo($name);
            $lang_file = $_lang_path["dirname"]."/".$_lang_path["filename"].".".$lang.".".$_lang_path["extension"];
            if(file_exists($last_dir."/".$lang_file)){
                $name = $lang_file;
            }

            // 最終的なテンプレートファイルの存在チェック
            $path = $last_dir."/".$name;
            if(!file_exists($path)){
                return null;
            }
            return $path;
        }
        return null; // string:xxxx はテンプレートファイルじゃない
    }
    public function fetch($name, $custom_check = true)
    {
        // カスタムテンプレートチェック

        $dir = $this->getScriptPath();
        // default 環境
        $path = $dir."/".$name;
        // stage 環境
        $stage_dir = $dir."_stage";
        $stage_path = $stage_dir."/".$name;
        if(file_exists($stage_path)){
            $this->setScriptPath($stage_dir);
        }
        // custom 環境
        $custom_dir = $dir."_custom";
        $custom_path = $custom_dir."/".$name;
        if(file_exists($custom_path)){
            $this->setScriptPath($custom_dir);
        }

        // 最終的なスクリプトパス
        $last_dir = $this->getScriptPath();
        if(substr($name, 0, 7) != "string:"){
            // ファイルパスの情報取得
            $_name = pathinfo($name);
            // デモ用ファイルパス
            $_demo_name = $_name["dirname"]."/".$_name["filename"]."_demo.".$_name["extension"];

            // デモモードチェック
            $demo_mode = false;
            if(isset($this->siteconf->value["demo"])){
                $demo_mode = $this->siteconf->value["demo"];
            }
            if($demo_mode == false && isset($this->thismod->value["module"]) && isset($this->thismod->value["module"]["demo"])){
                $demo_mode = $this->thismod->value["module"]["demo"];
            }

            // デモモードでデモ用ファイルがあれば採用
            if($demo_mode == true && file_exists($last_dir."/".$_demo_name)){
                $name = $_demo_name;
            }

            // 言語テンプレートがあればそちらを、なければデフォルト
            // 言語選択は、「日本語 < ブラウザの言語設定 < クッキーに保存された設定」の優先度で採用
            // 言語テンプレートは「ファイル名.言語名.html」の書式であることが前提
            // 言語テンプレートが無い場合「ファイル名.html」が採用される
            $locale = new Zend_Locale();
            $lang = $locale->getLanguage(); // デフォルトはブラウザの言語設定
            $cookie_name = "select_lang"; // 固定値
            if(array_key_exists($cookie_name, $_COOKIE) && $_COOKIE[$cookie_name] != "") {
                $lang = $_COOKIE[$cookie_name];
                $lang_support = Zend_Registry::get("config")->site->language->support;
                // 言語設定があればサポート言語かをチェック
                if($lang_support != "" && !in_array($lang, explode(",",$lang_support))){
                    Zend_Registry::get("logger")->warn("Invalid language was selected: ".$lang);
                    $lang = Zend_Registry::get("config")->site->language->default;
                }
                // 言語設定が無ければ日本語を強制する
                else if($lang_support == ""){
                    $lang = "ja";
                }
            }
            $_lang_path = pathinfo($name);
            $lang_file = $_lang_path["dirname"]."/".$_lang_path["filename"].".".$lang.".".$_lang_path["extension"];
            if(file_exists($last_dir."/".$lang_file)){
                $name = $lang_file;
            }

            // 最終的なテンプレートファイルの存在チェック
            $path = $last_dir."/".$name;
            if(!file_exists($path)){
                throw new Zend_Exception("not found template: <br/>$path");
            }
            $this->__fetched_files[] = $path;
        }

        if($this->__cache_id){
            Zend_Registry::get("logger")->debug("Smarty template cache_id = ".$this->__cache_id.", template = ".$name);
        }

        // レイアウト言語定数ファイルがあれば読み込む
        $lang_const_file = $this->getLayoutDir()."/lang/".$lang.".php";
        if(file_exists($lang_const_file)){
            require_once($lang_const_file);
        }
        // モジュール言語定数ファイルがあれば読み込む
        $lang_const_modfile = str_replace("/templates", "", $dir)."/lang/".$lang.".php";
        $lang_const_modfile_custom = str_replace("/templates", "", $dir)."/lang/custom/".$lang.".php";
        if(file_exists($lang_const_modfile_custom)){
            require_once($lang_const_modfile_custom);
        }
        else if(file_exists($lang_const_modfile)){
            require_once($lang_const_modfile);
        }

        $data = $this->_smarty->fetch($name, $this->__cache_id);
        return $data;
    }
    /**
     * レイアウトディレクトリ設定
     *
     * 第一引数で指定したレイアウトディレクトリを設定する。
     * 第二引数に値があれば、レイアウトディレクトリ内の第二引数の値をレイアウトファイルとして採用する。
     * 第二引数に値が無い場合は index.html をレイアウトファイルとして採用する。
     * ただし、第一引数が ":" で区切られた文字列の場合、第二引数に値が指定されていたとしても
     * ":" 以降の文字列がレイアウトファイルとして採用されることとなっている。
     */
    public function setLayoutDir($dir = null, $file = null)
    {
        if( preg_match("/([^:]+):([^:]+).html/", $dir, $match) ){
            $this->_layout_dir = $match[1];
            $this->_layout_file = $match[2].".html";
        } else if( preg_match("/([^:]+):([^:]+)/", $dir, $match) ){
            $this->_layout_dir = $match[1];
            $this->_layout_file = $match[2].".html";
        } else if( $file != "" ) {
            $this->_layout_dir = $dir;
            $this->_layout_file = $file;
        } else {
            $this->_layout_dir = $dir;
            $this->_layout_file = "index.html";
        }
    }
    public function setLayoutFile($file = null)
    {
        $this->_layout_file = $file;
    }
    public function getLayoutDir()
    {
        return $this->_layout_dir;
    }
    public function render($name)
    {
        $isMobile = false;
        $controller = Zend_Controller_Front::getInstance()
                ->getRequest()
                ->getControllerName();

        // 共通表示要素(webapp/config/config.ini で指定したやつ)の登録
        // -> $contents で取得するテンプレートに埋めるため、contents を
        //    生成する直前で実行する。
        $r = Zend_Registry::getInstance();
        if(isset($r["renderCache"]) && is_array($r["renderCache"])){
            foreach($r["renderCache"] as $item){
                $obj = $item["obj"];
                $method = $item["method"];
                $arg = $item["arg"];
                $html = $obj->$method($arg);
                $this->_smarty->assign($item["smarty_var"], $html);
            }
        }

        $carrier = Ao_Util_Ktai::getCarrierCode();
        if($carrier != 0){ // 0: pc, 1: docomo, 2: au, 3: softbank
            $isMobile = true;
            $this->_layout_file = "mobile.html";
            $name_pc = $name;
            $_name = pathinfo($name);
            if($controller == "error"){
                $name = $_name["dirname"]."/".$_name["basename"];
            } else {
                $this->_smarty->assign("carrier_code", $carrier);
                $name = $_name["dirname"]."/mobile/".$_name["basename"];
            }
            // モバイル用テンプレートがあれば採用、なければ PC 用を使う
            if(file_exists($name)){
                $contents = $this->fetch($name);
            } else {
                $contents = $this->fetch($name_pc);
            }
        } else {
            $contents = $this->fetch($name);
        }

        // レイアウトが設定され存在していればレイアウトに埋めたものを返す
        $layout = $this->_layout_dir."/".$this->_layout_file;
        if($this->_layout_dir 
            && $this->_layout_file
            && file_exists($layout)
        ){
            $this->setScriptPath($this->_layout_dir);
            $this->_smarty->assign("layout_file", $layout);
            $this->_smarty->assign("fetched_files", $this->__fetched_files);
            $this->_smarty->assign("query_log", Zend_Registry::get("query_log"));
            $this->_smarty->assign("contents", $contents);
            //return $this->_smarty->fetch($this->_layout_file);
            if($isMobile){
                if($carrier == Ao_Util_Ktai::CARRIER_DOCOMO){
                    header("Content-Type: application/xhtml+xml");
                }
                if($carrier == Ao_Util_Ktai::CARRIER_EZWEB){
                    header("Content-type: application/xhtml+xml; charset=Shift_JIS");
                    header("Cache-Control: no-cache");
                }
                $this->assign("doctype", Ao_Util_Ktai::getDoctype());
                //return mb_convert_kana(mb_convert_encoding($this->_smarty->fetch($this->_layout_file), "SJIS", "UTF-8"), "kas", "SJIS");
                return mb_convert_kana(mb_convert_encoding($this->fetch($this->_layout_file), "SJIS", "UTF-8"), "kas", "SJIS");
            } else {
                //return $this->_smarty->fetch($this->_layout_file);
                return $this->fetch($this->_layout_file, $this->__cache_id);
            }
        } else {
            return $contents;
        }
    }
/*
    public function render($name)
    {
        return $this->_smarty->fetch($name);
    }
*/
 
    public function getScriptPaths()
    {
        // Smarty オブジェクトを生成した直後に template_dir には
        // array("./templates/") のみがセットされている。
        // Smarty-2.x 時代からの流れで、ここは文字列が入っている
        // ことが想定されるため、3.x 以上のときは template_dir が
        // 配列ならばそのまま、文字列ならば array() でくくるという
        // 処理をするようにした。2011/05/28
        if($this->smarty_version >= "3.0.0"){
            if(is_array($this->_smarty->template_dir)){
                return $this->_smarty->template_dir;
            } else {
                return array($this->_smarty->template_dir);
            }
        } else {
            return array($this->_smarty->template_dir);
        }
    }
 
    public function setBasePath($path, $classPrefix = 'Zend_View')
    {
    }
 
    public function addBasePath($string, $classPrefix = 'Zend_View')
    {
    }

    // ao original
    //     レイアウトディレクトリ内のファイルを出力(fetch)
    public function layout($name, $dir = null)
    {
        if($dir == ""){
            $dir = $this->_layout_dir;
        }
        if(file_exists($dir ."/". $name)){
            return $this->_smarty->fetch($dir."/".$name);
        } else {
            throw new Zend_Exception("no layout: $name");
        }
    }

    // 一つのテンプレートで複数のキャッシュを持たせる時に使用する
    // http://www.smarty.net/docsv2/ja/caching.multiple.caches.tpl
    function setCacheId($cache_id = "")
    {
        Zend_Registry::get("logger")->debug("Smarty Cache set = ".$cache_id);
        $this->__cache_id = $cache_id;
    }
    function isCached($name, $cache_id = null, $compile_id = null){

        // begin:----- fetch() の先頭のソースをこぴぺ
        $dir = $this->getScriptPath();
        // default 環境
        $path = $dir."/".$name;
        // stage 環境
        $stage_dir = $dir."_stage";
        $stage_path = $stage_dir."/".$name;
        if(file_exists($stage_path)){
            $this->setScriptPath($stage_dir);
        }
        // custom 環境
        $custom_dir = $dir."_custom";
        $custom_path = $custom_dir."/".$name;
        if(file_exists($custom_path)){
            $this->setScriptPath($custom_dir);
        }
        // end:----- fetch() の先頭のソースをこぴぺ

        return $this->_smarty->isCached($name, $cache_id, $compile_id);
    }
    function cacheLifetime($time = null)
    {
        if($time !== null){
            $this->_smarty->cache_lifetime = $time;
        }
        return $this->_smarty->cache_lifetime;
    }
    function caching($flag = true)
    {
        Zend_Registry::get("logger")->debug("Smarty template caching = ".(int)$flag);
        $this->_smarty->caching = $flag;
    }
    function clearCache($template, $cache_id, $compile_id)
    {
        $this->_smarty->clearCache($template, $cache_id, $compile_id);
    }
    function getCaching()
    {
        return $this->_smarty->caching;
    }
}
