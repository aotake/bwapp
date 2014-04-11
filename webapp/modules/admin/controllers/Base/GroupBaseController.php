<?php
/**
 * Admin Group Base Controller
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
class Admin_GroupBaseController extends Ao_Controller_ModAdmAction
{
    public function indexAction()
    {
        $manager = new Manager_Group($this);
        //$this->view->items = $manager->getGroup();
        $parent_id = $this->getRequest()->getParam("parent_id");
        if(!$parent_id){
            $parent_id = 0;
        }
        $with_children = true;
        // 親のカテゴリ情報
        if($parent_id > 0){
            $category = current($manager->getGroup($parent_id));
        } else {
            $category = null;
        }
        // 子のカテゴリ一覧
        $items = $manager->getGroupByParentId($parent_id, $with_children);
        // カテゴリパス
        $path = $manager->getPath($parent_id);
        $this->view->parent_id = $parent_id;
        $this->view->parent = $category;
        $this->view->items = $items;
        $this->view->path = $path;
    }
    public function listAction()
    {
        $manager = new Manager_Group($this);
        //$this->view->items = $manager->getGroup();
        $parent_id = $this->getRequest()->getParam("parent_id");
        if(!$parent_id){
            $parent_id = 0;
        }
        $this->view->select_array = $manager->getGroupSelectArray();
    }
    public function detailAction()
    {   
        $id = $this->getRequest()->getQuery("id");
        if(!$id){ 
            $msg = "ID が取得できませんでした";
            throw new Zend_Exception($msg);
        }
        $manager = new Manager_Group($this);
        $vo = $manager->getGroup($id);
        if(!$vo){ 
            $msg = "データが取得できませんでした";
            throw new Zend_Exception($msg);
        }
        $this->view->item = current($vo);
    }

    public function newAction()
    {
        $req = $this->getRequest();
        $form = new Form_Group($this);
        $this->view->form = $form->formElements();
        $this->view->action_name 
            = $this->_registry["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/".$req->getActionName()
                ."/";
        if($req->isPost()){
            $manager = new Manager_Group($this);
            $params = $req->getParams();
            //$manager->convYmdToIntPubDate($params);
            //$manager->convYmdToIntExprDate($params);
            $vo = $manager->getGroupVo($params);
            // 階層確認
            if($vo->get("parent_id") > 0){
                $parent = current($manager->getGroup($vo->get("parent_id")));
                $vo->set("depth", (int)$parent->get("depth") + 1);
            } else {
                $vo->set("depth", 0);
            }
            $manager->saveGroup($vo);
            $this->view->message = "登録が完了しました";
            $this->getFrontController()->setParam("noViewRenderer", true);
            $c = $req->getControllerName();
            $a = $req->getActionName();
            $this->view->item = $vo;
            echo $this->view->render($c."/".$a."_complete.html");
            exit;
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
        $manager = new Manager_Group($this);
        $vos = $manager->getGroup($id);
        $vo = current($vos);

        // カテゴリパス
        $path = $manager->getPath($id);

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
        $form = new Form_Group($this);
        $form->setParams($params);
        $action_name = $conf->site->root_url
            ."/".$req->getModuleName()
            ."/".$req->getControllerName()
            ."/edit-confirm/";

        // ビューにアサインする
        $this->view->form = $form->formElements();
        $this->view->action_name = $action_name;
        $this->view->params = $params;
        $this->view->category = $vo;
        $this->view->path = $path;

        /// ----- 追加
        //$updir_top = $this->_registry["webappDir"]
        //    ."/modules/".$req->getModuleName()
        //    ."/uploads"
        //    ;
        //$manager = new Manager_Image($this);
        //$target = "category";
        //$target_id = $req->getParam("id");
        //$manager->setUpdirTop($updir_top);
        //$vos = $manager->getImageByTarget($target, $target_id);
        //$this->view->images     = $vos;
        //$this->view->target     = $target;
        //$this->view->target_id  = $target_id;
        //$this->view->img_action = $conf->site->root_url
        //    ."/".$req->getModuleName()
        //    ."/".$req->getControllerName()
        //    ."/image-regist/";
    }
    public function editConfirmAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("記事のIDが取得できませんでした");
        }
        // DB データを取得する
        $manager = new Manager_Group($this);
        $vos = $manager->getGroup($id);
        $vo = current($vos);

        // 送信データを取得
        $_params = $req->getParams();

        // _forward 用変数
        $m = $req->getModuleName();
        $c = $req->getControllerName();

        // バリデーション
        $form = new Form_Group($this);
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
        $manager = new Manager_Group($this);
        $vos = $manager->getGroup($id);
        $vo = current($vos);
        
        // 送信データを取得
        $_params = $req->getParams();
        
        // _forward 用変数
        $m = $req->getModuleName();
        $c = $req->getControllerName();
            
        // バリデーション
        $form = new Form_Group($this);
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
            $vo = $manager->getGroupVo($params);
            $manager->saveGroup($vo);
     
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
        $manager = new Manager_Group($this);
        $vos = $manager->getGroup($id);
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
        $manager = new Manager_Group($this);
        $_item = $manager->getGroup($id);
        if(!$_item){
            throw new Zend_Exception("データが取得できませんでした");
        }
        $item = current($_item);

        // 削除
        $manager->deleteGroup($item->get("id"));

        $this->view->item = $item;
    }

    public function imageRegistAction()
    {
        $auth = new Zend_Session_Namespace("auth");
        $req = $this->getRequest();
        $uploader = new Ao_Util_ImgUploader();
        $exts_str = (string)$this->_registry["config"]->upload->allow_exts;
        $exts = explode(",", $exts_str);
        $updir_top = $this->_registry["webappDir"]
            ."/modules/".$req->getModuleName()
            ."/uploads"
            ;
        $updir = $updir_top."/".$auth->user_id;

        if(!is_dir($updir_top)){
            throw new Zend_Exception("No upload dir found: ".$updir_top);
        }

        if(!is_dir($updir)){
            if(!mkdir($updir)){
                throw new Zend_Exception("cannot create: $updir");
            }
        }

        // アップロード画像がある index を取得
        $upload_idx = $uploader->uploadFileIndex();

        foreach($upload_idx as $i){
            if($uploader->existUploadFile($i)){
                if(!$uploader->upload($updir, $exts,"userdata", $i)){
                    $code = $uploader->errorCode();
                    throw new Zend_Exception("error: file $i, code = $code ");
                }

                $up_filename = $uploader->uploadfile();
                $uploader->mkThumb($up_filename);
                $uploader->mkMobileImg($up_filename);
                $uploader->mkMobthumbImg($up_filename);
                // $uploader->renameTo($new_filename); // ファイル名を指定する
                $p["uid"]       = $auth->user_id;
                $p["target"]    = $req->getParam("target");      // テーブル名
                $p["target_id"] = $req->getParam("target_id");   // 対象の ID
                $p["file_name"] = $up_filename;
                $p["orig_name"] = $uploader->upOrigname();
                $p["mime_type"] = $uploader->upMimeType();
                $p["file_size"] = $uploader->upFilesize();
                $manager = new Manager_Image($this);
                $vo = $manager->getImageVo($p);
                $manager->saveImage($vo);
            }
        }
        $this->view->target_id = $req->getParam("target_id");
    }
    public function imageEditAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("IDが取得できませんでした");
        }
        // DB データを取得する
        $manager = new Manager_Image($this);
        $vos = $manager->getImage($id);
        $vo = current($vos);

        // _REQUEST データを DBデータをマージする
        $_params = $req->getParams();
        $params = array_merge($vo->toArray(), $_params);

        $reg = Zend_Registry::getInstance();
        $conf = $reg["config"];

        // フォーム要素を準備する
        $form = new Form_Image($this);
        $form->setParams($params);
        $action_name = $conf->site->root_url
            ."/".$req->getModuleName()
            ."/".$req->getControllerName()
            ."/image-edit-regist/";

        // ビューにアサインする
        $this->view->form = $form->formElements();
        $this->view->action_name = $action_name;
        $this->view->params = $params;
    }
    public function imageDeleteAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("IDが取得できませんでした");
        }
        // DB データを取得する
        $manager = new Manager_Image($this);
        $vos = $manager->getImage($id);
        $vo = current($vos);

        // ターゲットテーブルデータを取得
        $target_vo = $manager->getTargetData($vo);

        $reg = Zend_Registry::getInstance();
        $action_name = $reg["config"]->site->root_url
                ."/".$req->getModuleName()
                ."/".$req->getControllerName()
                ."/image-delete-do/";

        // ビューにアサインする
        $this->view->target = $target_vo;
        $this->view->action_name = $action_name;
        $this->view->item = $vo;
    }
    public function imageDeleteDoAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam("id");
        if(!$id){
            throw new Zend_Exception("IDが取得できませんでした");
        }

        // DB データを取得する
        $manager = new Manager_Image($this);
        $_item = $manager->getImage($id);
        if(!$_item){
            throw new Zend_Exception("データが取得できませんでした");
        }
        $item = current($_item);

        // ターゲットテーブルデータを取得
        $target_vo = $manager->getTargetData($item);

        // 削除
        $manager->deleteImage($item->get("id"));

        $this->view->item = $item;
        $this->view->target = $target_vo;
    }

    /**
     * tabledefAction()
     *
     * for debug Action
     */
    public function tabledefAction()
    {
        $model = new Model_Group();
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
