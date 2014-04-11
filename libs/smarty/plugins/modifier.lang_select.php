<?php
/**
 * Smarty plugin
 * 
 * @package Smarty
 * @subpackage PluginsModifier
 */
 
/**
 * Smarty lang_select modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     lang_select<br>
 * Purpose:  lang_select string for output
 * 
 * @author aotake <aotake at bmath dot org> 
 * @param string $string input string
 * @param string $lang lang_select type
 * @param string $char_set character set
 * @return string string
 */
function smarty_modifier_lang_select($string, $char_set = SMARTY_RESOURCE_CHAR_SET)
{
    // cookie から lang を特定（デフォルトは ja）
    if(array_key_exists("select_lang", $_COOKIE) && $_COOKIE["select_lang"]) {
        $lang = $_COOKIE["select_lang"];
    } else {
        $lang = "ja";
    }

    // config.ini からサポートする言語を取得
    $target_langs = explode(",", Zend_Registry::get("config")->site->language->support);
    
    // 文字列を言語毎の配列に分解
    $data = array();
    $match_count = 0;
    foreach($target_langs as $target_lang){
        if(preg_match("/\[$target_lang\](.+)\[\/$target_lang\]/", $string, $match)){
            $data[$target_lang] = $match[1];
            $match_count++;
        } else {
            $data[$target_lang] = null;
        }
    }

    // サポートする言語のデータが無い場合は入力データをそのまま返す
    if ($match_count == 0) {
        return $string;
    }
    // match_count > 0 で $lang の文があった
    else if ($data[$lang]) {
        return $data[$lang];
    }
    // match_count > 0 で $lang の文がなくて ja ならある
    else if ($data["ja"]) {
        return $data["ja"];
    }
    else {
        trigger_error("lang_select: unknown error",E_USER_WARNING);
    }

} 

?>
