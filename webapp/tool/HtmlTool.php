<?php
ini_set("date.timezone", "Asia/Tokyo");
require_once dirname(__FILE__)."/CodeTool.php";
class HtmlTool extends CodeTool
{
    protected $user;
    public $profile;
    public $config;

    public function __construct($ldir = null, $adir = null, $hdir = null)
    {
        parent::__construct($ldir, $adir, $hdir);

        $this->user   = null;
        $this->config = null;


        $config_filepath = $this->webapp_dir."/config/config.ini";
        $this->setupEnv();
        $section = $this->getApplicationEnv();
        $this->loadConfig($config_filepath, $section);
        $this->config = Zend_Registry::get("config");
        $this->setupDbAdapter();
        $this->setupLooger("htmltool.log");
        $this->setupModulePath();

        $auth = new Zend_Session_Namespace("auth");
        $registry = Zend_Registry::getInstance();
        if(isset($registry["isDbError"]) == false
            || $registry["isDbError"] == false
        ) {
            if( !empty( $auth->user_id ) ){
                $this->user = $this->getUserInfo($auth->user_id);
                $this->user->set("passwd", null);
            }
        } else {
            // DB エラー時は認証情報をクリアする
            Zend_Session::namespaceUnset("auth");
            $this->user = null;
        }
    }
    // URL 系
    public function rootUrl()
    {
        echo $this->config->site->root_url;
    }
    public function logoutUrl( $category = null )
    {
        echo $this->config->site->root_url . "/logon/logout/";
    }
    public function loginUrl( $category = null )
    {
        echo $this->config->site->root_url . "/logon/";
    }
    public function layoutUrl()
    {
        $root_url = $this->config->site->root_url;
        $layout_name = $this->config->site->default_layout;
        echo $root_url."/layout/".$layout_name;
    }
    public function imageUrl()
    {
        $root_url = $this->config->site->root_url;
        $layout_name = $this->config->site->default_layout;
        echo $root_url."/layout/".$layout_name."/images";
    }
    public function cssUrl()
    {
        $root_url = $this->config->site->root_url;
        $layout_name = $this->config->site->default_layout;
        echo $root_url."/layout/".$layout_name."/css";
    }
    public function sitename()
    {
        echo $root_url = $this->config->site->name;
    }
    public function keyword()
    {
        echo $root_url = $this->config->site->keyword;
    }
    public function description()
    {
        echo $root_url = $this->config->site->description;
    }
    public function defaultTitle()
    {
        echo $root_url = $this->config->site->default_title;
    }
    public function defaultSubTitle()
    {
        echo $root_url = $this->config->site->default_sub_title;
    }
    public function copyright()
    {
        echo $root_url = $this->config->site->copyright;
    }

    public function user( $key )
    {
        $user_cols = array("name", "email", "login");

        if( !$this->user ){
            $res = null;
        } else if( in_array( $key, $user_cols) ){
            $res = $this->user->get( $key );
        } else {
            Zend_Registry::get("logger")->debug(__METHOD__.",".__LINE__.", Invalid key: $key");
            $res = null;
        }
        echo $res;
    }
    public function profile( $key )
    {
        // プロフィールモジュール使っていて、まだプロフィールデータを取得していなければ取得
        if( ! is_dir($this->webapp_dir."/modules/profile") ){
            Zend_Registry::get("logger")->debug(__METHOD__.",".__LINE__.", profile module is not used");
            return null;
        }

        if( ! $this->user ){ // ログインしていない
            return null;
        }

        if( !$this->profile || ! $this->profile instanceof Ao_Vo_Abstract ){
            $this->profile = $this->getUserProfile( $this->user->get("uid") );
        }

        $k = "prof_".$key;
        if( $this->profile ){
            $res = $this->profile->get( $k );
        } else {
            $res = null;
        }
        echo $res;
    }
    public function profileUrl()
    {
        if( !$this->profile ){
            echo null;
        }

        echo $this->config->site->root_url."/profile";
    }
    public function loggedIn()
    {
        if($this->user){
            return 1;
        } else {
            return 0;
        }
    }
    public function isAdmin()
    {
        if( !$this->user ){
            return null;
        }

        // 管理者 = 1, 非管理者 = 0
        $is_admin = 0;

        $roles = $this->user->get("role");
        if( is_array( $roles ) ){
            foreach( $roles as $r ){
                if( $r->get("name") == "admin" ){
                    $is_admin = 1;
                    break;
                }
            }
        }
        return $is_admin;
    }
    public function welcome($template = "ようこそ %s さん")
    {
        if( $this->user ){
            $msg = sprintf($template, $this->user->get("name"));
        } else {
            $msg = sprintf($template, "ゲスト");
        }
        echo $msg;
    }

    public function getConfig()
    {
        return $this->config;
    }

    /** Private Method **/
    private function getUserProfile( $user_id )
    {
        $model = new Profile_Model_Basic();
        $select = $model->select();
        $select->where("uid = ?", $user_id );
        $res = $model->fetchAll( $select );
        if( $res ) {
            return current( $res );
        } else {
            return null;
        }
    }
    private function getUserInfo( $user_id )
    {
        $manager = new Manager_User();
        $user_model = new Model_User();
        try {
            $rowset = $user_model->find($user_id);
            if (count($rowset)) {
                $user_vo = current($rowset);
                // ユーザデータがあったら Role 情報も付与してやる
                $user_vo->set("role", $manager->getRolesByUid($user_id));
                return current($rowset);
            }
        } catch(Zend_Exception $e){
            $r = Zend_Registry::getInstance();
            $r["logger"]->err("uid = $user_id, method=".__METHOD__.",file=".__FILE__.",line=".__LINE__.",".$e->getMessage());
            throw $e;
        }
        return false;
    }

    public function addTool( $module )
    {
        $class = Ao_Util_Str::toPascal( $module )."_HtmlTool";
        $htmltool_filepath = $this->webapp_dir . "/modules/". $module. "/tool/".$class.".php";
        if( file_exists( $htmltool_filepath ) ){
            require_once $htmltool_filepath;
            if( class_exists( $class, false ) ){
                $this->{$module} = new $class();
            } else {
                $msg = "class is not defined: $class";
                Zend_Registry::get("logger")->debug(__METHOD__.",".__LINE__.", ".$msg);
            }
        }
    }
}

$htmltool = new HtmlTool();
