<?php
/**
 * Action Controller
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
 * @package       Ao.Controller
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
abstract class Ao_Controller_Action extends Zend_Controller_Action
{

    protected $_registry;

    public function init(){
        $this->_registry = Zend_Registry::getInstance();
        $this->initSmarty();

        // ヘルパーの登録
        Zend_Controller_Action_HelperBroker::addPath( $this->_registry["webappDir"]."/helpers" , "My" );
        $this->_common_helper = $this->_helper->getHelper('CommonHelper');
    }

    public function setLayoutByDirname($layout_name)
    {
        // レイアウトを変更
        $config = Zend_Registry::get("config");
        $layout_url = $config->site->root_url."/layout/".$layout_name;
        $layout_dir = $config->site->root_path."/layout/".$layout_name;
        if($layout_name != "none" && !file_exists($layout_dir)){
            throw new Zend_Exception("no layout dir: $layout_dir");
        }
        $this->view->setLayoutDir($layout_dir);
        $this->view->assign("layout_name",$layout_name);
        $this->view->assign("layout_url", $layout_url);
    }

    public function initSmarty()
    {
        $request = $this->getRequest();
        $module  = $request->getModuleName();
        $controller  = $request->getControllerName();
        $action  = $request->getActionName();

        $dirs    = $this->getFrontController()->getControllerDirectory();
        if (empty($module) || !isset($dirs[$module])) {
            $module = 'default';
        }
 
        if($this->_registry["config"]->smarty->template_top){
            $conf = $this->_registry["config"]->smarty;
            $templates_dir = $conf->template_top."/".$module;
        } else {
            $templates_dir = dirname($dirs[$module]) . '/templates';
        }

        if (!is_dir($templates_dir)) {
            $msg = $module.": テンプレートディレクトリがありません";
            $this->_registry["logger"]->err($msg.", $templates_dir");
            throw new Zend_Exception($msg);
        }

        // Smarty3 にしたら this->view が Ao_Controller_Action オブジェクト
        // になっていることがあるのでチェック
        if(method_exists($this->view, "setScriptPath")){
            $this->view->setScriptPath($templates_dir);
        } else {
            $class = get_class($this->view);
            $methods = get_class_methods($class);
            ob_start();
            print implode(", ",$methods);
            $content = ob_get_contents();
            ob_end_clean();
            $msg = "setScriptPath method is not found: class $class has \n";
            $msg.= "$content<br>\n";
            $this->_registry["logger"]->err($msg);
            throw new Zend_Exception("予期しないエラーが検出されました");
        }

        // パラメータ
        if($this->_registry["config"]->system){
            $sysconf = $this->_registry["config"]->system->toArray();
            $sysconf = array_merge($sysconf, array(
                    "module" =>$module,
                    "controller" =>$controller,
                    "action" =>$action,
                )
            );
        } else {
            $sysconf = array(
                    "module" =>$module,
                    "controller" =>$controller,
                    "action" =>$action,
            );
        }
        $this->view->assign("sysconf", $sysconf);

        $this->view->assign("modconf", $this->_registry["modconf"]->toArray());
        if($this->_registry["modconf"]->$module){
            $this->view->assign("thismod", $this->_registry["modconf"]->$module->toArray());
        }
        $this->view->assign("application_env", $this->_registry["application_env"]);

        $this->view->assign("ssl", Ao_Util::isSsl());

        $urlinfo = parse_url($this->_registry["config"]->site->root_url);
        $this->view->assign("root_url_info", $urlinfo);

        // レイアウトを使う場合有効に：
        // ここで設定したパスが存在すれば Ao_View_Smarty::render() で採用される

        // -- default
        $layout_top = $this->_registry["layoutDir"];
        $layout_dir = $layout_top."/default";

        // {{{ 1. デフォルトモジュールディレクトリ名のレイアウトがあればそれで上書き
        $front = Zend_Controller_Front::getInstance();
        $default_module = $front->getDefaultModule();

        if($default_module != "default"
            && file_exists($layout_top."/".$default_module)
        ){
            $layout_dir = $layout_top."/".$default_module;
        }
        // }}}
        // {{{ 2. config.ini で指定したレイアウトがあればそれで上書き
        $conf_default_layout = $this->_registry["config"]->site->default_layout;

        if($module != "admin" 
            && $conf_default_layout != ""
            && file_exists($layout_top."/".$conf_default_layout)
        ){
            $layout_dir = $layout_top."/".$conf_default_layout;
        }
        // }}}
        // {{{ 3. カレントのモジュール名のレイアウトがあればそれで上書き
        if($module != "default" && file_exists($layout_top."/".$module)){
            $layout_dir = $layout_top."/".$module;
        }
        // }}}
        // {{{ 4. カレントのモジュールの config の module に指定があればそれで上書き
        if(!empty($this->_registry["modconf"]->$module->module->layout)){
            $_mconf = $this->_registry["modconf"]->$module->module;
            $modconf_layout = $_mconf->layout;

            // コントローラとアクションが指定されていれば
            // 指定コントローラとアクション時のみチェック
            if(!empty($_mconf->layout_controller)
                && !empty($_mconf->layout_action)
            ){
                $this->_registry["logger"]->debug("[DEPRECATED] this module ($module) layout config is legacy.");
                $_c = $_mconf->layout_controller;
                $_a = $_mconf->layout_action;
                if($controller == $_c && $action == $_a){
                    if(file_exists($layout_top."/".$modconf_layout)){
                        $layout_dir = $layout_top."/".$modconf_layout;
                    }
                }
            }
            // コントローラ指定有り、アクション指定無し（旧仕様）
            else if(!empty($_mconf->layout_controller)
                && !preg_match("/[:=|]/",$_mconf->layout_controller) // 旧仕様では = は含まない
                && empty($_mconf->layout_action)
            ){
                $this->_registry["logger"]->debug("[DEPRECATED] this module ($module) layout config is legacy.");
                $_c = $_mconf->layout_controller;
                $_cs = explode(",", $_c);

                if(in_array($controller,$_cs)){
                    if(file_exists($layout_top."/".$modconf_layout)){
                        $layout_dir = $layout_top."/".$modconf_layout;
                    }
                }
            }
            // コントローラ指定有り、アクション指定無し（2011/11/18仕様）
            else if(!empty($_mconf->layout_controller)
                && preg_match("/[:=|]/",$_mconf->layout_controller) 
                && empty($_mconf->layout_action)
            ){
                $_layout_controller_def = $_mconf->layout_controller;
                $_layout_controller_arr = explode("|", $_layout_controller_def);
                foreach($_layout_controller_arr as $item){
                    $tmp = explode("=", $item);
                    // コントローラ指定のみ
                    if(count($tmp) == 1){
                        if( $controller == $tmp[0] && file_exists($layout_top."/".$modconf_layout)){
                            $layout_dir = $layout_top."/".$modconf_layout;
                            break;
                        }
                    }
                    // コントローラ、アクションの両方指定
                    else if(count($tmp) == 2){
                        $_ctr = $tmp[0];
                        $_acts = explode(",", $tmp[1]);
                        if($controller == $tmp[0] && in_array($action, $_acts) && file_exists($layout_top."/".$modconf_layout)){
                            $layout_dir = $layout_top."/".$modconf_layout;
                            break;
                        }
                    }
                    else{
                        print count($tmp);
                        print_r($tmp);exit;
                        throw new Zend_Exception("invalid layout config", E_USER_ERROR);
                    }
                }
            }
            // コントローラアクションの指定なければ、常にチェック
            else if(file_exists($layout_top."/".$modconf_layout)){
                $layout_dir = $layout_top."/".$modconf_layout;
            }
        }
        // }}}
        // {{{ 5. カレントのモジュールの config の layout に指定があればそれで上書き
        //
        // config.ini の設定例（default モジュールの場合）
        // default.layout.sitemap.index = "sitemap_layout"
        // default.layout.index.sitemap = "sitemap_layout"
        // default.layout.index.contact = "twentyten"
        // default.layout.index.bbs     = "twentyeleven"
        //
        $mod_layout_conf = $this->_registry["modconf"]->{$module}->layout;
        if( isset($mod_layout_conf->{$controller}->{$action}) ){
            $layout_dir =  $layout_top ."/". $mod_layout_conf->{$controller}->{$action};
        }
        // }}}
        // {{{ 6. layout_admin が設定されていて、指定コントローラならそれを使う
        if(!empty($this->_registry["modconf"]->$module->module->layout_admin)){
            $_mconf = $this->_registry["modconf"]->$module->module;
            $modconf_layout = $_mconf->layout_admin;
            if(isset($_mconf->layout_admin_controller)){
                $_c = $_mconf->layout_admin_controller;
                $_cs = explode(",", $_c);
                if(in_array($controller, $_cs)){
                    if(file_exists($layout_top."/".$modconf_layout)){
                        $layout_dir = $layout_top."/".$modconf_layout;
                    }
                }
            }
        }
        // }}}

        $layout_file = "index.html";
        $this->view->setLayoutDir($layout_dir, $layout_file);
        //$this->view->setLayoutFile($layout_file);

        // モジュール CSS があるかをチェックする
        //     もしあれば layout で default module の ReadCssController
        //     に module 名を渡してレンダリングさせる
        $front = Zend_Controller_Front::getInstance();
        $moddir = $front->getModuleDirectory();
        $modcss = $moddir."/css/style.css";
        $cust_css = $moddir."/css/custom/style.css";
        $this->view->modcss_exists = file_exists($modcss) || file_exists($cust_css);

        // レイアウトURLを埋め込む
        $layout_url = $this->_registry["config"]->site->root_url
            ."/layout/".basename($this->view->getLayoutDir());
        $this->view->assign("layout_name", basename($this->view->getLayoutDir()));
        $this->view->assign("layout_url", $layout_url);
        $this->view->assign("layout_dir", $layout_dir);

        //$this->_registry["layoutUrl"] = $layout_url;
        $registry = Zend_Registry::getInstance();
        $registry["layoutUrl"] = $layout_url;

        // 全モジュール共通で実行すべき定義があれば実行
        // ただし $skip_ctrs に含まれるコントローラのときは実行をしない
        // 実行結果はレジストリに保存し view で出力する
        $skip_mods = array("admin");
        $skip_ctrs = array("readimage", "css", "error", "sorry", "admin", "loadcss");
        if(
            !in_array($module, $skip_mods)
            && !in_array($controller, $skip_ctrs)
            && isset($this->_registry["config"]->system)
        ){
            $sysconf = $this->_registry["config"]->system;
            if(isset($sysconf->common) && isset($sysconf->common->view_assign)){
                $defs = $sysconf->common->view_assign->toArray();
                foreach($defs as $smarty_var => $def){
                    // target = null すなわち全モジュールが対象 && でも skip_modules のモジュールは避ける
                    if(!isset($def["target"]) && isset($def["skip_modules"])){
                        $user_skip_mods = explode(",", $def["skip_modules"]);
                        if(in_array($module, $user_skip_mods)){
                            continue;
                        }
                    }
                    // target が設定されているけど書式が不正な時は処理を無視する
                    if($def["target"]){
                        if($def["target"] != $module."_".$controller."_".$action){
                            continue;
                        }
                    }
                    // target がマッチするか全てのモジュール(target=null)なら処理内容を登録する
                    $pat = "/([A-Za-z0-9_]+)::([^()]+)\((.*)\)$/";
                    if(preg_match($pat, $def["call"], $m)){
                        ob_start();
                        $incfile = str_replace("_", "/", $m[1]).".php";
                        $inc_res = include_once($incfile);
                        $content = ob_get_contents();
                        ob_end_clean();
                        if($inc_res == false){
                            $msg = "Not found: $incfile<br>at block cache";
                            Ao_Util_Debug::trace(debug_backtrace(), $msg, $content);
                            trigger_error($content, E_USER_ERROR);
                        }
                        $obj = new $m[1]($this);
                        $registry["renderCache"][] = array(
                            "smarty_var" =>$smarty_var,
                            "obj"  => $obj,
                            "method" => $m[2],
                            "arg" =>$m[3]
                        );
                        //$this->view->$smarty_var = $obj->$m[2]($m[3]);
                    }
                }
            }
        }

        $smarty = $this->view->getEngine();
        $smarty->compile_id = $this->smartyCompileId();
    }
    public function smartyCompileId()
    {
        $request = $this->getRequest();
        $module  = $request->getModuleName();
        $controller  = $request->getControllerName();
        $action  = $request->getActionName();
        return $module . '_' . $action . '_' . $controller;
    }
    public function preDispatch()
    {
        if(isset($_SERVER)){
            $ip = $_SERVER["REMOTE_ADDR"];
            Zend_Registry::get("logger")->debug($ip.", REQUEST_URI=".$_SERVER["REQUEST_URI"]);
            Zend_Registry::get("logger")->debug($ip.", HTTP_REFERER=".$_SERVER["HTTP_REFERER"]);
        }
        parent::preDispatch();
    }
    public function postDispatch()
    {
        parent::postDispatch();
        // CommonHelper を実行し、結果を配列で受け取る
        // 結果の配列のキーは Smarty 変数名であると仮定する
        $common_helper_data = $this->_common_helper->execute();
        foreach( $common_helper_data as $helper_key => $helper_value){
            $this->view->assign( $helper_key, (int)$helper_value );
        }
    }
}
