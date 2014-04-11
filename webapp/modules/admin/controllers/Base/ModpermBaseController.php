<?php
/**
 * Admin Modperm Base Controller
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
 * @package       Ao.modules.admin.Controller.Base
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class Admin_ModpermBaseController extends Ao_Controller_ModAclAction
{
    public function indexAction()
    {
        // 全件表示の場合はこちら
        //$manager = new Manager_Modperm($this);
        //$this->view->items = $manager->getModperm();

        // ページャを使う
        $req = $this->getRequest();
        $m = $req->getModuleName();
        $c = $req->getControllerName();
        $kw = $req->getParam("kw");
        $manager = new Manager_Modperm($this);
        $count = $manager->getCount($kw);
        $this->view->count = $count;
        $reg = Zend_Registry::getInstance();
        $conf = $reg["config"];
        $offset = (int)$req->getParam("offset");
        $limit = $req->getParam("limit");
        if($limit == ""){
            $reg = Zend_Registry::getInstance();
            $limit = $reg["config"]->search->limit;
        }
        $base_url = $conf->site->root_url."/$m/$c/?limit=$limit&amp;offset=";
        $nav = new Ao_Util_Pagenav();
        $nav->set("total", $count);
        $nav->set("perpage", $limit);
        $nav->set("current", $offset);
        $nav->set("url", $base_url);
        $this->view->pagenav = $nav->renderNav();
        $this->view->items = $manager->getPage($limit, $offset, null, $kw);
        $this->view->kw = $kw;
    }
    public function detailAction()
    {   
        $id = $this->getRequest()->getQuery("id");
        if(!$id){ 
            $msg = "ID が取得できませんでした";
            throw new Zend_Exception($msg);
        }
        $manager = new Manager_Modperm($this);
        $vo = $manager->getModperm($id);
        if(!$vo){ 
            $msg = "データが取得できませんでした";
            throw new Zend_Exception($msg);
        }
        $this->view->item = current($vo);
    }

    public function newAction()
    {
        $req = $this->getRequest();
        $form = new Form_Modperm($this);
        $this->view->form = $form->formElements();
        $this->view->action_name 
            = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/".$req->getActionName()
                ."/";
        if($req->isPost()){
            $manager = new Manager_Modperm($this);
            $params = $req->getParams();
            //$manager->convYmdToIntPubDate($params);
            //$manager->convYmdToIntExprDate($params);

            // _forward 用変数
            $m = $req->getModuleName();
            $c = $req->getControllerName();
        
            // バリデーション
            $form = new Form_Modperm($this);
            $form->validate($params);
            if(!$form->errorNum()){ 
                $vo = $manager->getModpermVo($params);
                $manager->saveModperm($vo);
                $this->view->message = "登録が完了しました";
                $this->getFrontController()->setParam("noViewRenderer", true);
                $c = $req->getControllerName();
                $a = $req->getActionName();
                echo $this->view->render($c."/".$a."_complete.html");
                exit;
            }
            $this->view->error = $form->errors();
        }
    }
    public function editAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("IDが取得できませんでした");
        }
        // DB データを取得する
        $manager = new Manager_Modperm($this);
        $vos = $manager->getModperm($id);
        $vo = current($vos);

        // 情報公開日を年月日時分に分割してセットする
        //$manager->convIntToYmdPubDate($vo);

        // 公開終了日を年月日時分に分割してセットする
        //$manager->convIntToYmdExprDate($vo);

        // _REQUEST データを DBデータをマージする
        $_params = $req->getParams();
        $params = array_merge($vo->toArray(), $_params);

        $reg = Zend_Registry::getInstance();
        $conf = $reg["config"];

        // フォーム要素を準備する
        $form = new Form_Modperm($this);
        $form->setParams($params);
        $action_name = $conf->site->root_url
            ."/".$req->getModuleName()
            ."/".$req->getControllerName()
            ."/edit-confirm/";

        // ビューにアサインする
        $this->view->form = $form->formElements();
        $this->view->action_name = $action_name;
        $this->view->params = $params;
    }
    public function editConfirmAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("記事のIDが取得できませんでした");
        }
        // DB データを取得する
        $manager = new Manager_Modperm($this);
        $vos = $manager->getModperm($id);
        $vo = current($vos);

        // 送信データを取得
        $_params = $req->getParams();

        // _forward 用変数
        $m = $req->getModuleName();
        $c = $req->getControllerName();

        // バリデーション
        $form = new Form_Modperm($this);
        $form->validate($_params);
        if($form->errorNum()){
            $this->view->error = $form->errors();
            return $this->_forward("edit", $c, $m);
        }

        // DB データと送信データをマージする
        $params = array_merge($vo->toArray(), $_params);

        $reg = Zend_Registry::getInstance();
        $conf = $reg["config"];

        // POST で確認ボタンもしくは登録ボタンなら処理をする
        if(
            $req->isPost()
            && ($req->getPost("submit") == "確認"
                || $req->getPost("submit") == "登録")
        ){
            // フォーム要素を準備する
            $form->setParams($params);
            $action_name = $conf->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/edit-regist/";

            // 送信データを hidden タグにうめてアサイン
            $this->view->form = $form->confirmElements();
            $this->view->action_name = $action_name;
            $this->view->params = $params;
        }
        else {
            $req->setParam("sys_message", "不正なアクションを検出しました");
            return $this->_forward("edit", $c, $m);
        }
    }
    public function editRegistAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("ID が取得できませんでした");
        }
        // DB データを取得
        $manager = new Manager_Modperm($this);
        $vos = $manager->getModperm($id);
        $vo = current($vos);
        
        // 送信データを取得
        $_params = $req->getParams();
        
        // _forward 用変数
        $m = $req->getModuleName();
        $c = $req->getControllerName();
            
        // バリデーション
        $form = new Form_Modperm($this);
        $form->validate($_params);
        if($form->errorNum()){
            // エラーがあったらメッセージをアサインしてフォームに戻す
            $this->view->error = $form->errors();
            return $this->_forward("edit", $c, $m);
        }
        
        // DB データと送信データをマージする
        $params = array_merge($vo->toArray(), $_params);
                
        // ボタンに応じたアクション
        if($req->isPost() && $req->getPost("submit") == "戻る"){
            return $this->_forward("edit", $c, $m);
        }   
        else if(
            $req->isPost() && $req->getPost("submit") == "登録"
        ){      
            // フォーム要素を準備する
            $form->setParams($params);
            $conf = $this->_registry["config"];
            $action_name = $conf->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/";
        
            //$manager->convYmdToIntPubDate($params);
            //$manager->convYmdToIntExprDate($params);
            $vo = $manager->getModpermVo($params);
            $manager->saveModperm($vo);
     
            $this->view->action_name = $action_name;
            $this->view->params = $params;
        }
    }
    public function deleteAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("IDが取得できませんでした");
        }
        // DB データを取得する
        $manager = new Manager_Modperm($this);
        $vos = $manager->getModperm($id);
        $vo = current($vos);

        $reg = Zend_Registry::getInstance();
        $action_name = $reg["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/delete-do/";

        // ビューにアサインする
        $this->view->action_name = $action_name;
        $this->view->item = $vo;
    }
    public function deleteDoAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("IDが取得できませんでした");
        }

        // DB データを取得する
        $manager = new Manager_Modperm($this);
        $_item = $manager->getModperm($id);
        if(!$_item){
            throw new Zend_Exception("データが取得できませんでした");
        }
        $item = current($_item);

        // 削除
        $manager->deleteModperm($item->get("id"));

        $this->view->item = $item;
    }

    /**
     * tabledefAction()
     *
     * for debug Action
     */
    public function tabledefAction()
    {
        $model = new Model_Modperm();
        $info = $model->info();
        $html = null;
        $html.= "<h2>{$info["name"]}</h2>\n";
        $html.= "<table border='1'>\n";
        $html.="<tr><th>column name</th><th>data type</th><th>default</th></tr>\n";
        foreach($info["metadata"] as $row){            $html .= "<tr><td>{$row["COLUMN_NAME"]}</td><td>{$row["DATA_TYPE"]}</td><td>{$row["DEFAULT"]}</td></tr>\n";        }
        $html.= "</table>\n";
        $html.= "Primary key: ".implode(",", $info["primary"]);
        print $html;
        exit; 
    }
}
