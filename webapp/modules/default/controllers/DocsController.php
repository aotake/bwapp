<?php
/**
 * Default Docs Controller
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
/**
 * 静的ページ表示
 *
 * config.ini の system.static_page_dir で指定したディレクトリの
 * Smarty テンプレートを読み込み静的ページを表示する。
 *
 * アクセス時は
 *
 *     http://ルートURL/docs/ページ名/
 *
 * のように行う。
 * 階層構造をもたせた static ページの場合は f=xxx/yyy/zzz.html
 * のように f オプションで指定する。これによりアクション名部分を
 * 無視して f の値でサーチする。
 *
 * 実際のファイルは
 *
 *     system.static_page_dirで指定したディレクトリ/ページ名.html
 *
 * となる。
 *
 * 認証等が必要ないページで採用。
 */
class DocsController extends Ao_Controller_Action
{
    protected $_config;
    protected $_logger;

    public function init()
    {
        parent::init();
        $req = $this->getRequest();
        $mod = $req->getModuleName();
        $ctr = $req->getControllerName();
        $act = $req->getActionName();

        // f オプションでパス指定があったらそちらを使う
        $filepath = $req->getParam("f");
        if($filepath){
            $act = $filepath;
        }
        // 拡張子を取り除く
        $act = preg_replace("/(.+)\.html$/", "\\1", $act);

        $param = array("page" => $act);
        if($act != "index") return $this->_forward("index", $ctr, $mod, $param);
    }
    //public function preDispatch()
    //{
    //}
    public function indexAction()
    {
        //$viewHelper = $this->_helper->getHelper("viewRenderer");
        //$viewHelper
        //    ->setViewScriptPathSpec(":controller/:action.:suffix")
        //    ->setViewScriptPathNoControllerSpec($page.'.:suffix')
        //    ->setViewSuffix("html");
        $filename = $this->getRequest()->getParam("page").".html";
        $dir = Zend_Registry::get("config")->system->static_page_dir;
        echo  $this->view->fetchStatic($filename, $dir);
        exit;
    }
}
