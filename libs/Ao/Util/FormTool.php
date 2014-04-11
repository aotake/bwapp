<?php
/**
 * Form Generate Utility
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
class Ao_Util_FormTool
{
    public function formValidate($config_array)
    {
        $carrier = Ao_Util_Ktai::getCarrierCode();

        $zv = new Zend_View();
        $zv->doctype('XHTML1_STRICT');

        $validator = new Ao_Util_Validator();

        $r = Zend_Controller_Front::getInstance()->getRequest();
        $err = array();
        foreach($config_array as $name => $def){
            // 定義要素を | で分割
            $def_items = explode("|", $def);
            foreach($def_items as $v){
                if($v == "required"){
                    $err[$name][$v] = $validator->postRequired($name);
                }
                else if($v == "int"){
                    $err[$name][$v] = $validator->postIsInteger($name);
                }
                else if($v == "tel"){
                    if($validator->postExistVal($name)){
                        $err[$name][$v] = $validator->postTelFormat($name);
                    }
                }
                else if($v == "email"){
                    $err[$name][$v] = $validator->postEmailFormat($name);
                }
                else if(preg_match("/^(\/.*\/)$/",$v, $regex)){
                    $pattern = $regex[1];
                    $err[$name]["regex"] = $validator->postRegexFormat($name, $pattern);
                }
            }
        }
        return $err;
    }
    public function countError($err)
    {
        return Ao_Util_Validator::countError($err);
    }
    public function getFormElements($config_array)
    {
        $carrier = Ao_Util_Ktai::getCarrierCode();

        $zv = new Zend_View();
        $zv->doctype('XHTML1_STRICT');

        $r = Zend_Controller_Front::getInstance()->getRequest();
        $fe = array(); // form elements

        // config.ini の定義からフォーム要素を生成
        foreach($config_array as $name => $def){
            // 定義要素を | で分割
            $def_items = explode("|", $def);
            $default = null;
            foreach($def_items as $d_item){
                // 分割要素のキーと値を抽出
                if(preg_match("/([^=]+)=(.*)$/", $d_item, $m)){
                    ${$m[1]} = $m[2];
                    // [] でくくられている値は配列にして再保存
                    if(preg_match("/^\[(.+)\]$/", $m[2], $n)){
                        $a = array();
                        foreach(explode(",", $n[1]) as $_tmp){
                            if(preg_match("/=/", $_tmp)){
                                $_tmp2 = explode("=", $_tmp);
                                if($_tmp2[0] == "null"){
                                    $a[null] = $_tmp2[1];
                                } else {
                                    $a[$_tmp2[0]] = $_tmp2[1];
                                }
                            } else {
                                $a[] = $_tmp;
                            }
                        }
                        ${$m[1]} = $a;
                    }
                }
            }
            // フォーム要素のラベルを設定
            $fe[$name]["label"] = $label;

            // フォーム要素生成
            if($type == "container"){ // コンテナは要素名の配列を保持して後で置き換え処理する
                $fe[$name]["element"] = $elements;
            } else if($type == "text"){
                $req_value = $r->getParam($name);
                $default = $req_value ? $req_value : $default;
                $at = isset($after_text) ? $after_text : null;
                $fe[$name]["element"] = $zv->formText($name, $default, $attr).$at;
            } else if($type == "textarea") {
                $req_value = $r->getParam($name);
                $default = $req_value ? $req_value: $default;
                if($carrier){
                    $attr["cols"] = 20;
                    $attr["rows"] = 3;
                }
                $at = isset($after_text) ? $after_text : null;
                $fe[$name]["element"] = $zv->formTextarea($name, $default, $attr).$at;
            } else if($type == "select") {
                $req_value = $r->getParam($name);
                $default = $req_value ? $req_value : $default;
                $at = isset($after_text) ? $after_text : null;
                $fe[$name]["element"] = $zv->formSelect($name, $default, $attr, $options).$at;
            } else if($type == "checkbox") {
                $req_value = $r->getParam($name);
                $default = $req_value ? $req_value : $default;
                $sep = isset($sep) ? $sep : "null";
                switch($sep){
                case "br": $s = "<br/>";break;
                case "null": $s = " ";break;
                default: $s = null;break;
                }
                $at = isset($after_text) ? $after_text : null;
//                $fe[$name]["element"] = $zv->formMultiCheckbox($name, $default, $attr, $options,$s);
                $elem = array();
                $cnt = 0;
                foreach($options as $key => $val){
                    $attr["id"] = $name."-".$cnt++;
                    $attr["class"] = $name;
                    $attr["checked"] = in_array($key, $default) ? true: false;
                    $elem[] = $zv->formCheckbox($name."[]", $key, $attr)." ".$val;
                }
                $fe[$name]["element"] = implode("\n", $elem);
            } else if($type == "radio") {
                $req_value = $r->getParam($name);
                $default = ($req_value !== null) ? $req_value : $default;
                $sep = isset($sep) ? $sep : "null";
                switch($sep){
                case "br": $s = "<br/>";break;
                case "null": $s = " ";break;
                default: $s = null;break;
                }
                $at = isset($after_text) ? $after_text : null;
                $fe[$name]["element"] = $zv->formRadio($name, $default, $attr, $options,$s);
            }
            // 必須項目ではない変数は unset しておく
            unset($after_text);
        }

        // コンテナ定義があったら要素名の配列をフォーム要素で置き換える
        foreach($fe as $name => $items){
            if(is_array($items["element"]) && count($items["element"]) > 0){
                $replace = array();
                foreach($items["element"] as $target){
                    $replace[] = $fe[$target]["element"];
                    unset($fe[$target]);
                }
                $fe[$name]["element"] = implode("", $replace);
                // 元の要素名を覚えておく
                $fe[$name]["base_elements"] = $items["element"];
            }
        }
        return $fe;
    }
    public function appendErrorMsg(&$f_elements, &$error)
    {
        foreach($f_elements as $name => $item){
            if(array_key_exists("base_elements",$item)){
                $err = array();
                foreach($item["base_elements"] as $base_element){
                    //$err = array_merge($err, $error[$base_element]);

                    // array_merge だと第二引数側でエラーが無い場合
                    // 第一引数にエラーがあっても消去されるため、
                    // 引数毎に値がある方を保持する
                    foreach($error[$base_element] as $k => $msg){
                        if(!isset($err[$k])){
                            $err[$k] = $msg;
                        }
                    }
                }
                $f_elements[$name]["error"] = $err;
            } else {
                if(array_key_exists($name, $error)){
                    $f_elements[$name]["error"] = $error[$name];
                } else {
                    $f_elements[$name]["error"] = null;
                }
            }
        }
    }
    public function getConfirmElements($config_array)
    {
        $carrier = Ao_Util_Ktai::getCarrierCode();

        $zv = new Zend_View();
        $zv->doctype('XHTML1_STRICT');

        $r = Zend_Controller_Front::getInstance()->getRequest();
        $fe = array(); // form elements
        foreach($config_array as $name => $def){
            // 定義要素を | で分割
            $def_items = explode("|", $def);
            $default = null;
            foreach($def_items as $d_item){
                // 分割要素のキーと値を抽出
                if(preg_match("/([^=]+)=(.*)$/", $d_item, $m)){
                    ${$m[1]} = $m[2];
                    // [] でくくられている値は配列にして再保存
                    if(preg_match("/^\[(.+)\]$/", $m[2], $n)){
                        $a = array();
                        foreach(explode(",", $n[1]) as $_tmp){
                            if(preg_match("/=/", $_tmp)){
                                $_tmp2 = explode("=", $_tmp);
                                if($_tmp2[0] == "null"){
                                    $a[null] = $_tmp2[1];
                                } else {
                                    $a[$_tmp2[0]] = $_tmp2[1];
                                }
                            } else {
                                $a[] = $_tmp;
                            }
                        }
                        ${$m[1]} = $a;
                    }
                }
            }
            // フォーム要素のラベルを設定
            $fe[$name]["label"] = $label;
            // フォーム要素生成
            $req_value = $r->getParam($name);
            $default = $req_value ? $req_value : $default;
            if($type != "container"){
                $fe[$name]["hidden"] = $zv->formHidden($name, $default);
            }
            $at = isset($after_text) ? $after_text : null;
            if($type == "container"){
                // 仮で hidden に要素名の配列を入れる
                $fe[$name]["hidden"] = $elements;
            } else if($type == "text"){
                $fe[$name]["value"] = $default ? $default.$at : null;
            } else if($type == "text" || $type == "textarea"){
                $fe[$name]["value"] = $default ? nl2br($default).$at : null;
                $fe[$name]["raw_value"] = $default;
            } else if($type == "select" || $type == "radio") {
                $fe[$name]["value"] = array_key_exists($default, $options) ? $options[$default].$at : null;
            } else if($type == "checkbox") {
                $disp_value = array();
                $hidden_items = array();
                foreach($default as $item){
                    $disp_value[] = $options[$item] ? $options[$item].$at : null;
                    $hidden_items[] = $zv->formHidden($name."[]", $item);
                }
                $fe[$name]["hidden"] = implode("\n", $hidden_items);
                $fe[$name]["value"] = implode(", ", $disp_value);
            }
            unset($after_text);
        }

        // コンテナ定義があったら要素名の配列をフォーム要素で置き換える
        foreach($fe as $name => $items){
            if(is_array($items["hidden"]) && count($items["hidden"]) > 0){
                $h_replace = array();
                $v_replace = array();
                foreach($items["hidden"] as $target){
                    $h_replace[] = $fe[$target]["hidden"];
                    $v_replace[] = $fe[$target]["value"];
                    unset($fe[$target]);
                }
                $fe[$name]["hidden"] = implode("", $h_replace);
                $fe[$name]["value"] = implode("", $v_replace);
            }
        }
        return $fe;
    }
}
