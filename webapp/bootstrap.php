<?php
/**
 * bootstrap
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
 * @package       Ao.webapp
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Webapp
{
    static private $_webappDir;
    static private $_registry = null;

    static public function bootstrap($webapp_dir, $config_section)
    {
        self::$_webappDir = $webapp_dir;
        // タイムゾーン設定
        date_default_timezone_set('Asia/Tokyo');

        // ライブラリパス設定
        $base_dir = dirname($webapp_dir);
        $incpath = array(
            $base_dir."/libs",        // Zend, Ao
            $base_dir."/libs/smarty", // Smarty.class.php
            $base_dir."/libs/pear",   // 手動いれた PEAR ライブラリ
            $base_dir."/webapp/libs", // Manager, Model, Vo, Form
            $base_dir."/webapp/extlibs", // 外部ライブラリ(Smarty 以外)
        );
        $_path = ini_get("include_path").":".implode(":",$incpath);
        ini_set("include_path", $_path);

try {
        // 自動ロード設定
        require_once 'Zend/Loader/Autoloader.php';
        //$autoloader = Zend_Loader_Autoloader::getInstance();
        //$autoloader->setFallbackAutoloader(true);
        Zend_Loader_Autoloader::getInstance()
            ->setFallbackAutoloader(true)
            ->pushAutoloader(NULL, 'Smarty_' )
            ->pushAutoloader(NULL, 'owa_' );

        // for debug
        $b = new Ao_Util_Benchmark();// for debug

        // ログ設定
        //$b->initStart("setup_log"); // for debug
        $log_dir = $webapp_dir."/log";
        if(!is_dir($log_dir) || !is_writable($log_dir)){
            print "$log_dir is not writable";exit;
        }
        $log_path = $log_dir."/".date("Ymd").".log";
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream($log_path);
        $logger->addWriter($writer);
        //$b->stop("setup_log"); // for debug

//        $logger->debug(">>>>>>>>".$_SERVER["REQUEST_URI"]); // for debug
        // PHP エラーハンドラー
        //set_error_handler("ao_error_handler", E_ALL);
        //$b->initStart("setup_error_handler"); // for debug
        register_shutdown_function("Ao_Util_PhpShutdownHandler::execute");
        //$b->stop("setup_error_handler"); // for debug

        // セッション開始
        //$b->initStart("setup_session"); // for debug
        self::_startSession();
        //$b->stop("setup_session"); // for debug

        // レジストリに使い回しリソースを登録
        //$b->initStart("setup_registry"); // for debug
        $registry = Zend_Registry::getInstance();
        self::$_registry = $registry;
        $registry["layoutDir"] = $base_dir."/html/layout";
        $registry["webappDir"] = $webapp_dir;
        $registry["logger"] = $logger;
        $registry["application_env"] = APPLICATION_ENV;
        $registry["query_log"] = array("start mypid" => getmypid(), "log" => array());
        //$b->stop("setup_registry"); //  for debug

        // 設定を読み込む
        //$b->initStart("load_config"); // for debug
        $config_section = APPLICATION_ENV;
        $config = self::_loadConfig($webapp_dir, $config_section);
        $registry['config'] = $config;
        //$b->stop("load_config"); // for debug

        if($config->system->admin->notify_fatal_error && !$config->system->admin->email){
            throw new Zend_Exception("no system.admin.email in webapp config");
        }

} catch( Exception $e ){
// コンフィグロードまでにエラーがあった
        self::_errorHtml($exception, "step1");
}
// 以下はコンフィグをロード後
try {

        // site.html_local が指定されていたらそこに URL に対応するファイルがあるか確認
        // ファイルが見つかったら include して終了。
        // ファイルがなければ通常の処理をする。
        $html_local_dir      = Zend_Registry::get("config")->site->html_local;
        if( $html_local_dir ) {
           $b->initStart("check_html_local"); // for debug
            // アプリケーショントップのパスまでを除去
            $app_html_local      = preg_replace("!^".Zend_Registry::get("config")->site->app_path."!", "", $_SERVER["REQUEST_URI"]);
            // パスの最後が '/' なら除去
            $html_local_filepath = preg_replace("!(.+)/$!", "\\1", $html_local_dir.$app_html_local);
            // 指定パスがディレクトリなら /index.php を付け足す
            if( is_dir( $html_local_filepath ) ){
                $html_local_filepath = $html_local_filepath."/index.php";
            }
            // ファイルが存在するか
            if( file_exists( $html_local_filepath ) ){
                Zend_Registry::get("logger")->debug("bootstrap, check html_local, include local file: $html_local_filepath");
                // HtmlTool 読込
                require_once $webapp_dir."/tool/HtmlTool.php";
                // モジュール HtmlTool があれば読み込む
                $mod_htmltools = explode(",", Zend_Registry::get("config")->site->htmltool);
                if( $mod_htmltools ){
                    foreach($mod_htmltools as $mod){
                        $htmltool->addTool($mod);
                        Zend_Registry::get("logger")->debug("bootstrap, check html_local, add module HtmlTool: $mod");
                    }
                }
                include $html_local_filepath;
                $b->stop("check_html_local"); // for debug
                Zend_Registry::get("logger")->debug("bootstrap, check html_local, benchmark check_html_local: ".$b->score("check_html_local")."sec");
                exit;
            } else {
                Zend_Registry::get("logger")->debug("bootstrap, check html_local, No file: $html_local_filepath");
            }
            $b->stop("check_html_local"); // for debug
        }

        // memcached 利用のキャッシュ(オブジェクト生成は 0.002sec程度）
//        $b->initStart("memcached"); // for debug
        if($config->memcached && $config->memcached->use_memcached){
            $frontend = array(
                'lifetime' => $config->memcached->frontend_option->lifetime,
                'automatic_serialization' => $config->memcached->frontend_option->automatic_serialization
            );
            $backend  = array(
                'compressoion' => $config->memcached->backend_option->automatic_serialization
            );
            $memcached = Zend_Cache::factory('Output',    // frontend
                                             'Memcached', // backend
                                             $frontend,   // frontend option
                                             $backend);   // backend option
            //$registry["memcached"] = $memcached;
            $registry["cached"] = $memcached;
        } else {
            // memcached を使わない時は File キャッシュを使う
            //$registry["memcached"] = null;
            $frontendOptions = array( 'automatic_serialization' => true);
            $backendOptions  = array( 'cache_dir' => self::$_registry["webappDir"].'/temporary/cache');
            $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
            $registry["cache"] = $cache;
        }
//        $b->stop("memcached"); // for debug

        // プラグイン, DB, OWA などチェック不要なコントローラはフラグを立てる
//        $b->initStart("is_skip_action"); // for debug
        $is_skip = self::_isSkipAction($base_dir);
//        $b->stop("is_skip_action"); / for debug

        // デバッグモードではない場合は error_reporting を off にする
        if(!$config->site->debug){
            ini_set("error_reporting", 0);
        }
        if($config->site->error_reporting){
            ini_set("error_reporting", $config->site->error_reporting);
        }
        // OWA を使う場合は OWA の INCLUDE_DIR にパスを通す
if($is_skip == false){
//        $b->initStart("check_owa"); // for debug
        if(@$config->site->analytics->site_id && @$config->site->analytics->root_path){
            require_once $base_dir."/html/owa/owa_env.php";
            $owa_path = array(
                OWA_PATH,             // eventQueue.phpのため
                OWA_INCLUDE_DIR,      // Snoopy.class.php のため
                OWA_PEARLOG_DIR,      // LOG_xxxx のため
                OWA_PHPMAILER_DIR,
                OWA_HTTPCLIENT_DIR,
            );
            ini_set("include_path", ini_get("include_path").":".implode(":", $owa_path));
            // OWA で使う Autloader でロード出来ないファイルを手動で読み込んでおく
            require_once "Snoopy.class.php";
        }
//        $b->stop("check_owa"); // for debug
}

        // DB 利用サイトか未使用サイトかをチェック
//        $b->initStart("init_db"); // for debug
        if(!isset($config->system->use_db) || $config->system->use_db == true){
            // DB 初期化
            if (self::_initDb() == false) {
                $registry["isDbError"] = true;
            }
        }
//        $b->stop("init_db"); // for debug

        // モジュールのコントローラディレクトリを教える
        //      webappDir/modules ディレクトリ内のディレクトリは
        //      モジュールディレクトリとして処理する
//        $b->initStart("set_controller_dirs"); // for debug
        $modules_dir = $webapp_dir . '/modules';
        $front = Zend_Controller_Front::getInstance();
        //$front = Ao_Controller_Front::getInstance();
        if(isset($config->system->default_module)){
            $default_module = $config->system->default_module;
        } else {
            $default_module = "default";
        }
        if(isset($config->system->admin_module)){
            $admin_module = $config->system->admin_module;
        } else {
            $admin_module = "admin";
        }
        $controller_dirs = self::_loadModules($modules_dir, $default_module, $admin_module);
        $front->setControllerDirectory($controller_dirs);
//        $b->stop("set_controller_dirs"); // for debug

        // 利用コントローラディレクトリからモジュール設定をロードする
//        $b->initStart("load_modconf"); // for debug
        $modconf = self::_loadModuleConfig($controller_dirs);
        $registry["modconf"] = new Zend_Config($modconf);
        $registry["moddirs"] = self::_moduleDirsBy($controller_dirs);
//        $b->stop("load_modconf"); // for debug

        // モジュールライブラリにパスを通す
        //      ->controller_dirs を渡して dirname() して抽出
//        $b->initStart("iniset_again"); // for debug
        self::_iniSetModulePath($controller_dirs);
//        $b->stop("iniset_again"); // for debug

        // ルーティング
        self::_addRouter($webapp_dir);

        // プラグイン登録
if($is_skip == false){
//        $b->initStart("register_plugin"); // for debug
        $front->registerPlugin(new Ao_Controller_Plugin_Security());
        $front->registerPlugin(new Ao_Controller_Plugin_Dbcheck());
        $front->registerPlugin(new Ao_Controller_Plugin_ErrorHandler());
        $front->registerPlugin(new Ao_Controller_Plugin_Maintenance());
        // OWAを使うサイトで読み込むプラグイン
        if(@$config->site->analytics->site_id){
            $front->registerPlugin(new Ao_Controller_Plugin_Analytics());
            $front->registerPlugin(new Ao_Controller_Plugin_AnalyticsResults());
            $front->registerPlugin(new Ao_Controller_Plugin_AnalyticsLogin());
        }
//        $b->stop("register_plugin"); // for debug
}

        // ディレクトリパーミッションチェック
//        $b->initStart("check_perm"); // for debug
        self::_checkMod($registry);
//        $b->stop("check_perm"); // for debug

        // 携帯チェック
//        $b->initStart("check_ktai"); // for debug
        self::_ktaiPreProcess();
//        $b->stop("check_ktai"); // for debug

        // Smarty 設定
//        $b->initStart("setup_smarty"); // for debug
        self::_setupViewSmarty($registry);
//        $b->stop("setup_smarty"); // for debug

//        $b->initStart("dispatch"); // for debug
        $front->dispatch();
//        $b->stop("dispatch"); // for debug
} catch (Exception $exception) {
        self::_errorHtml($exception, "step2");
}

//        if($is_skip == false){
//            self::logBenchmark($b, $is_skip);
//        }
        $_SESSION["pgpool_error_retry"] = 0;
    }
    static private function logBenchmark(&$b, $is_skip = false)
    {
        $repo = $b->getRepo();
        ob_start();
        print "\n";
        $total = 0;
        foreach($repo as $target => $data){
            $score = (double)($data["_end"]-$data["_start"]);
            printf("%20s: %.8f\n", $target, $score);
            $total += $score;
        }
        printf("%20s: %.8f\n", "SUM", $total);
        $content = ob_get_contents();
        ob_end_clean();
        Zend_Registry::get("logger")->debug($content);
    }
    static private function _isSkipAction($base_dir = null)
    {
        $is_skip = false;
        $config = Zend_Registry::get("config");
        $root_url = str_replace(array("http://", "https://"), "", $config->site->root_url);
        $req_url_all = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
        $str = str_replace($root_url, "", $req_url_all);
        $params = explode("/", $str);
        array_map("strtolower", $params);

        // プラグインロード、DB初期化をスキップするコントローラ
        // -> DB が不要なコントローラのみ指定する
        $skip_target_array = array("loadcss", "error", "readimage");

        $date = date("Ymd");
        if(in_array($params[1], $skip_target_array)){
            error_log(">>>>>> def controller, ".$params[1]." found\n", 3, $base_dir."/webapp/log/$date.log");
            $is_skip = true;
        }
        if(count($params) > 2 && in_array($params[2], $skip_target_array)){
            error_log(">>>>>> mod controller, ".$params[2]." found\n", 3, $base_dir."/webapp/log/$date.log");
            $is_skip = true;
        }
        return $is_skip;
    }
    static private function _startSession()
    {
        //$webapp_dir = self::$_webappDir;
        //$config_path = $webapp_dir."/config/session.ini";
        //$config = new Zend_Config_Ini($config_path, "production");
        //Zend_Session::setOptions($config->toArray());
        Zend_Session::start();
    }
    static private function _ktaiPreProcess()
    {
        $carrier = Ao_Util_Ktai::getCarrierCode();
        if($carrier){
            // TODO:
            // ---> check http://gihyo.jp/dev/serial/01/mobilesite-php/0003
            // Softbank 3GC端末の場合は SJIS-win ではなく UTF8 とするらしい
            $enc = "UTF-8";
            mb_convert_variables($enc,   'SJIS-win', $_POST);
            mb_convert_variables($enc,   'SJIS-win', $_GET);
        }


    }
    static private function _initDb()
    {
        $config = self::$_registry["config"];
        $logger = self::$_registry["logger"];
        $db = $config->db->toArray();
        $dsn = $db["dsn"];
        $opt_arr = array(
            "host" => $dsn["host"],
            "username" => $dsn["username"],
            "password" => $dsn["password"],
            "dbname" => $dsn["dbname"],
            "charset" => $db["charset"],
        );
        if(isset($dsn["port"]) && is_numeric($dsn["port"])){
            $opt_arr["port"] = $dsn["port"];
        }

        try {
            $zdb_adapter = Zend_Db::factory($dsn["driver"], $opt_arr);
            $con = $zdb_adapter->getConnection();
            if(is_null($con)){
                $logger->err("getConnection が NULL を返した");
                return false;
            }
            $zdb_adapter->getProfiler()->setEnabled(true);
            Zend_Db_Table_Abstract::setDefaultAdapter($zdb_adapter);
        }
        catch(Exception $e){
            $logger->crit($e->getMessage());
            return false;
        }

        // meta info 用キャッシュ
        if($config->db->no_cache){
            $cache_files = glob(self::$_registry["webappDir"].'/temporary/cache/zend_cache*');
            foreach($cache_files as $f){
                unlink($f);
            }
        } else{
            $frontendOptions = array( 'automatic_serialization' => true);
            $backendOptions  = array( 'cache_dir' => self::$_registry["webappDir"].'/temporary/cache');
            $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
            Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        }

        // クエリキャッシュ初期化
        self::$_registry["queryCache"] = array("data"=>array());
/*
if(!is_writable(LIB_DIR.'/cache')){
    print "[Error] Not writable: ".LIB_DIR."/cache";
    exit;
}
$frontendOptions = array('automatic_serialization' => true);
$backendOptions  = array('cache_dir' => LIB_DIR.'/cache');
$cache = Zend_Cache::factory('Core','File', $frontendOptions, $backendOptions);
Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

$params = array(
            'host'     => XOOPS_DB_HOST,
            'username' => XOOPS_DB_USER,
            'password' => XOOPS_DB_PASS,
            'dbname'   => XOOPS_DB_NAME,
);
$driver = "Pdo_".ucfirst(XOOPS_DB_TYPE);
$db     = Zend_Db::factory($driver, $params);
$db->query('set names '.DB_CHARSET);
$db->getProfiler()->setEnabled(true);
Zend_Db_Table_Abstract::setDefaultAdapter($db);
*/
        self::$_registry["zdb_adapter"] = $zdb_adapter;
        return true;
    }
    static private function _addRouter($webapp_dir)
    {
        $filepath = $webapp_dir . "/config/custom/router.php";
        if(file_exists($filepath)){
            include $filepath;
        }
    }
    static private function _loadConfig($webapp_dir, $config_section = "production")
    {
        try{
            $config_path = $webapp_dir . "/config/config.ini";
            $custom_config_path = $webapp_dir . "/config/custom/config.ini";
            $init_opt = array("allowModifications" => true); // マージできるように
            if(file_exists($custom_config_path)){
                $config = new Zend_Config_Ini($custom_config_path, $config_section, $init_opt);
            } else {
                $config = new Zend_Config_Ini($config_path, $config_section, $init_opt);
            }

            if(Ao_Util::isSsl()){
                $sslconfig_path = $webapp_dir . "/config/ssl.ini";
                $custom_sslconfig_path = $webapp_dir . "/config/custom/ssl.ini";
                if(file_exists($custom_sslconfig_path)){
                    $sslconfig = new Zend_Config_Ini($custom_sslconfig_path, $config_section);
                    $config->merge($sslconfig);
                } else if(file_exists($sslconfig_path)){
                    $sslconfig = new Zend_Config_Ini($sslconfig_path, $config_section);
                    $config->merge($sslconfig);
                }
            }
            $config->setReadOnly(); // 読み込み専用に設定
        } catch( Zend_Exception $e ){
            Zend_Registry::get("logger")->debug($e->getMessage());
            throw $e;
        }

        return $config;
    }
    static private function _loadModules($modir, $default_module = null, $admin_module = null)
    {
        $dirs = glob($modir."/*");
        foreach($dirs as $d){
            $dirname = basename($d);
            //if(preg_match("/^_.+/", $dirname)) continue;
            $controller_dirs[$dirname] = $d."/controllers";
        }
        if($default_module){
            if(file_exists($modir."/".$default_module."/controllers")){
                $controller_dirs["default"]
                    = $modir."/".$default_module."/controllers";
                // デフォルトモジュール名がキーに入っていたら外す
                if($default_module != "default"){
                    unset($controller_dirs[$default_module]);
                }
            }
        }
        if($admin_module){
            if(file_exists($modir."/".$admin_module."/controllers")){
                $controller_dirs["admin"]
                    = $modir."/".$admin_module."/controllers";
                // デフォルトモジュール名がキーに入っていたら外す
                if($admin_module != "admin"){
                    unset($controller_dirs[$admin_module]);
                }
            }
        }
        return $controller_dirs;
    }
    static private function _moduleDirsBy($ctrs)
    {
        $mods = array();
        foreach($ctrs as $c){
            $mods[] = dirname($c);
        }
        return $mods;
    }
    static private function _loadModuleConfig($cdirs)
    {
        $registry = Zend_Registry::getInstance();
        $logger = $registry["logger"];
        $all_modconf = array();
        foreach($cdirs as $controller_dir){
            $modpath = dirname($controller_dir);
            $modconf = null;
            $modconf_arr = array();

            $custpath_legacy = $modpath."/config/custom.ini"; // 非推奨
            $custpath = $modpath."/config/custom/config.ini"; // 推奨
            $confpath = $modpath."/config/config.ini";        // 非推奨
            // カスタムコンフィグをロード
            if(file_exists($custpath)){
                $modconf = new Zend_Config_Ini($custpath, APPLICATION_ENV);
                $modconf_arr =  $modconf->toArray();
            }
            // カスタムコンフィグがなくて、古いカスタムコンフィグがあればロード
            else if(file_exists($custpath_legacy)){
                $modconf = new Zend_Config_Ini($custpath_legacy, APPLICATION_ENV);
                $modconf_arr =  $modconf->toArray();
                $logger->debug("[DEPRECATED] $custpath_legacy => please move(rename) to $custpath");
            }
            // カスタムがなければ通常コンフィグをロード
            else if(file_exists($confpath)){
                $modconf = new Zend_Config_Ini($confpath, APPLICATION_ENV);
                $modconf_arr =  $modconf->toArray();
                $logger->debug("[DEPRECATED] $confpath => please move(rename) to $custpath");
            }

            $all_modconf = array_merge($all_modconf, $modconf_arr);
        }
        return $all_modconf;
    }
    static private function _iniSetModulePath($c_dirs)
    {
        $setpath = array();
        foreach($c_dirs as $controller_dir){
            $_setpath = dirname($controller_dir)."/libs/default";
            if(file_exists($_setpath)){
                $setpath[] = $_setpath;
            }
            $_setpath = dirname($controller_dir)."/libs";
            if(file_exists($_setpath)){
                $setpath[] = $_setpath;
            }
        }
        $inc = ini_get("include_path");
        $setpath_str = implode(":", $setpath);
        ini_set("include_path", $setpath_str.":".$inc);
    }
    static private function _setupViewSmarty($registry)
    {
        $webapp_dir = $registry['webappDir'];
        $config = $registry['config'];
        $logger = $registry["logger"];
        require_once "Smarty.class.php";
        $smartyConf = $config->smarty->toArray();
        $smartyConf['cache_dir'] = $webapp_dir . '/temporary/cache/';
        $smartyConf['compile_dir'] = $webapp_dir . '/temporary/templates_c/';
        $smartyConf["left_delimiter"] = "<{";
        $smartyConf["right_delimiter"] = "}>";
        if (!is_dir($smartyConf['cache_dir'])
            || !is_writable($smartyConf['cache_dir'])
        ) {
            die('smartyキャッシュディレクトリに書き込みできません。');
        }
        if (!is_dir($smartyConf['compile_dir'])
            || !is_writable($smartyConf['compile_dir'])
        ) {
            die('smartyコンパイルディレクトリに書き込みできません。');
        }
        // テンプレートディレクトリの指定、Ao_Controller_Action::init()で行う
        $default_tpl_dir = null;

        // config.ini の site セクションを埋め込む
        $view = new Ao_View_Smarty($default_tpl_dir,$smartyConf);
        $view->assign("siteconf", $config->site->toArray());

        // ViewRendererヘルパーを使用する
        $vr = new Zend_Controller_Action_Helper_ViewRenderer();
        $vr->setView($view);
        $vr->setViewSuffix('html');
        Zend_Controller_Action_HelperBroker::addHelper($vr);
    }
    static private function _checkMod($registry)
    {
        $dir = $registry["webappDir"];
        $logger = $registry["logger"];
        $dirs = array(
            "/temporary/templates_c",
            "/temporary/cache",
            "/log",
            "/uploads",
        );

        $error_flag = false;
        $error_msg = array();
        foreach($dirs as $d){
            $path = $dir.$d;
            if(!is_writable($path)){
                $logger->debug("[ERROR] $path => is not writable.");
                $error_flag = true;
            }
        }

        if($error_flag){
            die("ディレクトリパーミッションをチェックしてください。（See logfile)");
        }
    }
    static private function _errorHtml($exception, $step = null)
    {
        $content = $exception->getMessage();

        // pgpool が segfault で落ちたとき $retry 回未満ならリトライする
        $db_config = Zend_Registry::get("config")->db;
        if( $step == "step2" && $db_config->type == "pgsql" && $db_config->dsn->port == 9999 ){
            if( preg_match("/SQLSTATE.+: General error: /", $exception->getMessage() ) ){
                $retry = 5;
                if( $_SESSION["pgpool_error_retry"] < $retry ){
                    @$_SESSION["pgpool_error_retry"]++;
                    Zend_Registry::get("logger")->debug("pgpool segfault, ".$_SESSION["pgpool_error_retry"]." times, retry to access: ".$_SERVER["REQUEST_URI"]);

                    if( $_SESSION["pgpool_error_retry"] >= 3 ){
                        sleep(1);
                    }
                    header("Location: ".$_SERVER["REQUEST_URI"]);exit;
                }
                $_SESSION["pgpool_error_retry"] = 0;
                $content = "ただいまサイトが混み合っています";
            }
        }

        $code = $exception->getCode();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $tostring = $exception->__toString();
        $trace_str= $exception->getTraceAsString();
        $trace = $exception->getTrace();
        ob_start();
        $trace_arr = array();
        foreach($trace as $item){
            if(count($item["args"]) > 0){
                $_args = array();
                foreach($item["args"] as $i => $_arg){
                    if(is_string($_arg)){
                        $_args[] = $i.":".$_arg;
                    } else {
                        if($_arg){
                            if(is_object($_arg)){
                                $_args[] = $i.":".get_class($_arg)." Object";
                            } else {
                                $_args[] = $i.": not null Object";
                            }
                        } else {
                            $_args[] = $i.": null Object";
                        }
                    }
                }
                if(count($_args)){
                    $arg = implode(", ",$_args);
                } else {
                    $arg = null;
                }
            } else {
                $arg = null;
            }
            $trace_arr[] = array(
                "file" => $item["file"],
                "line" => $item["line"],
                "function" => $item["function"],
                "class" => $item["class"],
                "type" => $item["type"],
                "args" => $arg
            );
        }
        print_r($trace_arr);
        $_trace = ob_get_contents();
        $_trace = nl2br(htmlspecialchars($_trace));
        ob_end_clean();

print <<<___HTML___
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>Error</title>
</head>
<body>
<style type="text/css">
dl.exception dt{
    clear: left;
    float: left;
    margin: 0 0 5px;
    width: 7.5em;
    border-left: solid 8px #faa;
    border-bottom: solid 1px #faa;
    padding-left: 5px;
    color: #000;
}
dl.exception dd {
    margin-bottom: 5px;
    margin-left: 8.5em;
    padding: 0 0 0 10px;
    border-left: solid 1px #faa;
}
div#exception_debug{
    padding: 10px;
}
h2#exception {
    border-left: 20px solid #aa0000;
    border-bottom: 1px solid #aa0000;
    background-color: #fdd;
    padding-left: 10px;
}
h3.exception {
    border-bottom: 1px solid #aa0000;
}
div#exception_message {
    text-align: center;
    padding: 50px 20px;
    font-weight: bold;
    margin: 0 10px 20px 10px;
    color: #f00;
    font-size: 120%;
    border: 1px solid #f66;
}
</style>

<h2 id="exception">Error (before dispatch)</h2>
<div id="exception_message">{$content}</div>

<div id="exception_debug">
<h3 class="exception">Deug Information</h3>
<dl class="exception">
    <dt>file</dt><dd>{$file}</dd>
    <dt>line</dt><dd>{$line}</dd>
    <dt>code</dt><dd>{$code}</dd>
    <dt>toString</dt><dd>{$tostring}</dd>
    <dt>trace_str</dt><dd>{$trace_str}</dd>
    <dt>trace(arr)</dt><dd><pre>{$_trace}</pre></dd>
</dl>
</body>
</html>
___HTML___;

    }

    /**
     * OWA など外部システムから Webapp Config を参照する
     */
    static public function loadConfig($webapp_dir, $config_section = null, $zend_path = ".")
    {
        if( $config_section== ""){
            $config_section = getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production';
        }
        // 現在のパスの設定を保存
        $current_path = ini_get("include_path");
        // Zend のパスを追加する
        ini_set("include_path", $current_path.":".$zend_path);

        // see: http://www.revulo.com/blog/20090524.html
        require_once 'Zend/Loader/Autoloader.php';
        // オートローダーが設定されていなければ設定する
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $is_fallback_autoloader = $autoloader->isFallbackAutoloader();
        if($is_fallback_autoloader == false){
            $autoloader->setFallbackAutoloader(true);
        }

        // webapp config を読み込むパス
        $conf = self::_loadConfig($webapp_dir, $config_section);

        // パスの設定を元に戻す
        ini_set("include_path", $current_path);

        // 元々オートローダーが効いていなければ無効化する
        if($is_fallback_autoloader == false){
           Zend_Loader_Autoloader::getInstance()
               ->setFallbackAutoloader(false);
        }
        return $conf;
    }
}
