<?php
/**
 * <{$TableName}>フォーム
 *
 * @category Form
 * @package Form_<{$ModuleName}>_Module_Bmathlog
 */
class <{$ModuleName}>_Form_<{$TableName}>
{
    /**
     * 呼出元コントローラ
     */
    protected $_controller;
    /**
     * Zend_Registry オブジェクト
     */
    protected $_registry;
    /**
     * Ao_View オブジェクト
     */
    protected $_zv;
    /**
     * リスト形式フォームフラグ
     */
    protected $_listform;

    /**
     * エラーメッセージ配列
     */
    protected $_errors;
    /**
     * エラー個数
     */
    protected $_error_num;

    /**
     * コンストラクタ
     *
     * @param Ao_ControllerAction コントローラオブジェクト
     * @return void
     */
    public function __construct(&$controller)
    {
        $this->_controller = $controller;
        $this->_zv = new Ao_View();
        $this->_registry = Zend_Registry::getInstance();
        $this->_listform = false;
    }
    /**
     * バリデータ
     *
     * @param void
     * @return void
     */
    public function validate()
    {
        $validator = new <{$ModuleName}>_Form_Validator_<{$TableName}>($this->_controller);
        if($this->_listform){
            $chk = $validator->listInput(); // リスト形式フォーム用
            $this->_errors = $chk;
            $this->_error_num = $chk["total_error_num"]; //リスト形式用
        } else {
            $chk = $validator->input(); // カード形式フォーム用
            $this->_errors = $chk["error"];
            $this->_error_num = $validator->countError($chk["error"]);
        }
    }   
    /**
     * エラーメッセージ配列
     *
     * @param void
     * @return array エラーメッセージの配列
     */
    public function errors()
    {
        return $this->_errors;
    }
    /**
     * エラー個数
     *
     * @param void
     * @return int
     */
    public function errorNum()
    {   
        return $this->_error_num;
    }
    /**
     * パラメータ設定
     *
     * @param array $params リクエストデータオブジェクト
     * @return void
     */
    public function setParams($params = array())
    {
        $req = $this->_controller->getRequest();
        foreach($params as $k => $v){
            $req->setParam($k, $v);
        }   
    } 
    /**
     * リストフォームフラグ
     *
     * @param boolean $flag
     * @return void
     */
    public function setListform($flag = false)
    {
        $this->_listform = $flag;
    }
    /**
     * フォームエレメント取得
     *
     * - DBカラム型が text で Form 要素定義が null または textarea なら textarea 要素
     * - DBカラム型が tinyint(1) または Form 要素定義が radio なら radio 要素
     * - Form 要素定義が select なら select 要素
     * - DBカラム名が id または uid またはカラム名の最後が "_id" でおわっていてフォーム要素が null か hidden なら hidden 要素
     * - DBカラム型が int かつ DBカラム名が "_date" で終わっている場合は Ymdhis それぞれを select要素
     * - DBカラム型が int かつ DBカラム名が "_time" で終わっている場合は h, i, s それぞれを select要素
     * - 上記以外は text 要素
     *
     * @param void
     * @return array フォームエレメントの配列
     */
    public function formElements()
    {
        return array(
<{foreach from=$tableInfo item=item}>
<{if $item.DATA_TYPE == "text" && ($item.FORM == "" || $item.FORM == "textarea")}>
            "<{$item.COLUMN_NAME}>" => $this->tarea<{$item.COLUMN_NAME|to_pascal}>(),
<{elseif $item.DATA_TYPE == "tinyint(1)" || $item.FORM == "radio"}>
            "<{$item.COLUMN_NAME}>" => $this->radio<{$item.COLUMN_NAME|to_pascal}>(),
<{elseif $item.FORM == "select"}>
            "<{$item.COLUMN_NAME}>" => $this->select<{$item.COLUMN_NAME|to_pascal}>(),
<{elseif $item.COLUMN_NAME == "id" || ($item.COLUMN_NAME|substr:-3 == "_id" && ($item.FORM == "" || $item.FORM == "hidden")) || $item.COLUMN_NAME == "uid"}>
            "<{$item.COLUMN_NAME}>" => $this->hidden<{$item.COLUMN_NAME|to_pascal}>(),
<{elseif $item.DATA_TYPE == "int" && $item.COLUMN_NAME|substr:-5 == "_date"}>
            "<{$item.COLUMN_NAME}>_y" => $this->select<{$item.COLUMN_NAME|to_pascal}>Y(),
            "<{$item.COLUMN_NAME}>_m" => $this->select<{$item.COLUMN_NAME|to_pascal}>M(),
            "<{$item.COLUMN_NAME}>_d" => $this->select<{$item.COLUMN_NAME|to_pascal}>D(),
            "<{$item.COLUMN_NAME}>_h" => $this->select<{$item.COLUMN_NAME|to_pascal}>H(),
            "<{$item.COLUMN_NAME}>_i" => $this->select<{$item.COLUMN_NAME|to_pascal}>I(),
            "<{$item.COLUMN_NAME}>_s" => $this->select<{$item.COLUMN_NAME|to_pascal}>S(),
<{elseif $item.DATA_TYPE == "int" && $item.COLUMN_NAME|substr:-5 == "_time"}>
            "<{$item.COLUMN_NAME}>_h" => $this->select<{$item.COLUMN_NAME|to_pascal}>H(),
            "<{$item.COLUMN_NAME}>_i" => $this->select<{$item.COLUMN_NAME|to_pascal}>I(),
            "<{$item.COLUMN_NAME}>_s" => $this->select<{$item.COLUMN_NAME|to_pascal}>S(),
<{else}>
            "<{$item.COLUMN_NAME}>" => $this->text<{$item.COLUMN_NAME|to_pascal}>(),
<{/if}>
<{/foreach}>
        );
    }
<{foreach from=$tableInfo item=item}>
<{*
  * TEXTAREA 型
  *
  *}>
<{if $item.DATA_TYPE == "text" && ($item.FORM == "" || $item.FORM == "textarea")}>
    public function tarea<{$item.COLUMN_NAME|to_pascal}>()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>]";
        } else {
            $name = "<{$item.COLUMN_NAME}>";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>",
            "class" => "<{$item.COLUMN_NAME}> ckeditor",
            "cols" => <{$item.FORM_ATTRIBUTE.cols|default:"60"}>,
            "rows" => <{$item.FORM_ATTRIBUTE.rows|default:"5"}>,
        );
        return $this->_zv->formTextarea($name, $default, $attr);
    }
<{*
  * radio タイプ
  *
  *}>
<{elseif $item.DATA_TYPE == "tinyint(1)" || $item.FORM == "radio"}>
    public function radio<{$item.COLUMN_NAME|to_pascal}>()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>");
        if($default !== 0 && $default == ""){
            // Request でデフォルト値が取得できなかった場合
            $default = "<{$item.DEFAULT}>";
        }
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>]";
        } else {
            $name = "<{$item.COLUMN_NAME}>";
        }
<{if $item.OPTION.ref_db}>
        $model = new <{$item.OPTION.module|ucfirst}>_Model_<{$item.OPTION.model|ucfirst}>();
        $select = $model->select()
            ->where("delete_flag = 0 or delete_flag is null")
            ->order("id asc")
            ;
        $vos = $model->fetchAll($select);
        $array = array();
        if($vos){
            foreach($vos as $i => $vo){
                $array[$vo->get("<{$item.OPTION.key}>")] = $vo->get("<{$item.OPTION.val}>");//"住所".($i + 1);
            }
        }
<{else if $item.OPTION.ref_conf}>
        $array = $this->_registry["modconf"]
                    -><{$module_name}>
                    -><{$item.OPTION.section}>
                    ->toArray();
<{else if $item.OPTION}>
        $array = array(
<{foreach from=$item.OPTION key=key item=val}>
            "<{$key}>" => "<{$val}>",
<{/foreach}>
        );
<{else}>
        $array = array(
            0 => "no",
            1 => "yes",
        );
<{/if}>
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>",
            "class" => "<{$item.COLUMN_NAME}>",
        );
        $listsep = "<{if $item.SEPARATOR == "br"}><br /><{else}>&nbsp;<{/if}>";
        return $this->_zv->formRadio($name, $default, $attr, $array, $listsep);
    }
<{*
  * SELECT タイプ
  *
  *}>
<{elseif $item.OPTION != "" && $item.FORM == "select"}>
    public function select<{$item.COLUMN_NAME|to_pascal}>()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>]";
        } else {
            $name = "<{$item.COLUMN_NAME}>";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>",
            "class" => "<{$item.COLUMN_NAME}>",
        );
<{if $item.OPTION.ref_db}>
        $model = new <{$item.OPTION.module|ucfirst}>_Model_<{$item.OPTION.model|ucfirst}>();
        $select = $model->select()
            ->where("delete_flag = 0 or delete_flag is null")
            ->order("id asc")
            ;
        $vos = $model->fetchAll($select);
        $array = array();
        if($vos){
            foreach($vos as $i => $vo){
                $array[$vo->get("<{$item.OPTION.key}>")] = $vo->get("<{$item.OPTION.val}>");//"住所".($i + 1);
            }
        }
<{else if $item.OPTION.ref_conf}>
        $array = $this->_registry["modconf"]
                    -><{$module_name}>
                    -><{$item.OPTION.section}>
                    ->toArray();
<{else if $item.OPTION}>
        $array = array(
<{foreach from=$item.OPTION key=key item=val}>
            "<{$key}>" => "<{$val}>",
<{/foreach}>
        );
<{/if}>
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
<{*
  * 出版日を INT で持つ
  *
  *}>
<{elseif $item.DATA_TYPE == "int" && $item.COLUMN_NAME|substr:-5 == "_date"}>
    public function select<{$item.COLUMN_NAME|to_pascal}>Y()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_y");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_y]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_y";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_y",
            "class" => "<{$item.COLUMN_NAME}>_y",
        );
        $start_y = date("Y") - 5; 
        $array[""] = "--";
        for($y = $start_y; $y < date("Y") + 10; $y++){
            $array[$y] = $y;
        }
        if(!$default){
            $default = date("Y");
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
    public function select<{$item.COLUMN_NAME|to_pascal}>M()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_m");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_m]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_m";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_m",
            "class" => "<{$item.COLUMN_NAME}>_m",
        );
        $array[""] = "--";
        for($m = 1; $m <= 12; $m++){
            $array[$m] = $m;
        }
        if(!$default){
            $default = date("m");
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
    public function select<{$item.COLUMN_NAME|to_pascal}>D()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_d");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_d]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_d";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_d",
            "class" => "<{$item.COLUMN_NAME}>_d",
        );
        $array[""] = "--";
        for($d = 1; $d <= 31; $d++){
            $array[$d] = $d;
        }
        if(!$default){
            $default = date("d");
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
    public function select<{$item.COLUMN_NAME|to_pascal}>H()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_h");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_h]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_h";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_h",
            "class" => "<{$item.COLUMN_NAME}>_h",
        );
        $array[""] = "--";
        for($h = 0; $h < 24; $h++){
            $array[$h] = $h;
        }
        if(!$default){
            $default = 0;
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
    public function select<{$item.COLUMN_NAME|to_pascal}>I()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_i");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_i]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_i";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_i",
            "class" => "<{$item.COLUMN_NAME}>_i",
        );
        $array[""] = "--";
        for($m = 0; $m <= 59; $m++){
            $array[$m] = $m;
        }
        if(!$default){
            $default = 0;
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
    public function select<{$item.COLUMN_NAME|to_pascal}>S()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_s");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_s]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_s";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_s",
            "class" => "<{$item.COLUMN_NAME}>_s",
        );
        $array[""] = "--";
        for($d = 0; $d <= 59; $d++){
            $array[$d] = $d;
        }
        if(!$default){
            $default = 0;
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
<{elseif $item.DATA_TYPE == "int" && $item.COLUMN_NAME|substr:-5 == "_time"}>
    public function select<{$item.COLUMN_NAME|to_pascal}>H()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_h");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_h]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_h";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_h",
            "class" => "<{$item.COLUMN_NAME}>_h",
        );
        $array[""] = "--";
        for($h = 0; $h < 24; $h++){
            $array[$h] = $h;
        }
        if(!$default){
            $default = 0;
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
    public function select<{$item.COLUMN_NAME|to_pascal}>I()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_i");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_i]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_i";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_i",
            "class" => "<{$item.COLUMN_NAME}>_i",
        );
        $array[""] = "--";
        for($m = 0; $m < 60; $m++){
            $array[$m] = $m;
        }
        if(!$default){
            $default = 0;
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
    public function select<{$item.COLUMN_NAME|to_pascal}>S()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>_s");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>_s]";
        } else {
            $name = "<{$item.COLUMN_NAME}>_s";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>_s",
            "class" => "<{$item.COLUMN_NAME}>_s",
        );
        $array[""] = "--";
        for($d = 0; $d < 60; $d++){
            $array[$d] = $d;
        }
        if(!$default){
            $default = 0;
        }
        return $this->_zv->formSelect($name, $default, $attr, $array);
    }
<{*
  * その他
  *
  *}>
<{else}>
    public function text<{$item.COLUMN_NAME|to_pascal}>()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>");
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>]";
        } else {
            $name = "<{$item.COLUMN_NAME}>";
        }
        $attr = array(
            "id" => "<{$item.COLUMN_NAME}>",
            "class" => "<{$item.COLUMN_NAME}>",
<{if $item.FORM_ATTRIBUTE.size}>
            "size" => <{$item.FORM_ATTRIBUTE.size}>,
<{elseif $item.DATA_TYPE == "int"}>
            "size" => 2,
<{else}>
            "size" => 30,
<{/if}>
        );
        return $this->_zv->formText($name, $default, $attr);
    }
<{/if}>
<{/foreach}>
    /**
     * 隠しタグエレメント取得
     *
     * @param void
     * @return array hiddenフォームエレメントの配列
     */
    public function confirmElements()
    {
        return array(
<{foreach from=$tableInfo item=item}>
            "<{$item.COLUMN_NAME}>" => $this->hidden<{$item.COLUMN_NAME|to_pascal}>(),
<{/foreach}>
        );
    }
<{foreach from=$tableInfo item=item}>
    public function hidden<{$item.COLUMN_NAME|to_pascal}>()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>");
<{if $item.COLUMN_NAME == "uid"}>
        if(!$default){
            $sess = new Zend_Session_Namespace("auth");
            $default = $sess->user_id;
        }
<{/if}>
        if($this->_listform){
            $id = $this->_controller->getRequest()->getParam("id");
            $name = "item[$id][<{$item.COLUMN_NAME}>]";
        } else {
            $name = "<{$item.COLUMN_NAME}>";
        }
        return $this->_zv->formHidden($name, $default);
    }
<{/foreach}>

    /**
     * タグエレメント取得
     *
     * @param void
     * @return array hiddenフォームエレメントの配列
     */
    public function showElements()
    {
        return array(
<{foreach from=$tableInfo item=item}>
            "<{$item.COLUMN_NAME}>" => $this->show<{$item.COLUMN_NAME|to_pascal}>(),
<{/foreach}>
        );
    }
<{foreach from=$tableInfo item=item}>
    public function show<{$item.COLUMN_NAME|to_pascal}>()
    {
        $req = $this->_controller->getRequest();
        $default = $req->getParam("<{$item.COLUMN_NAME}>");

<{if $item.FORM == "radio" && $item.OPTION}>
<{if $item.OPTION.ref_db}>
        $model = new <{$item.OPTION.module|ucfirst}>_Model_<{$item.OPTION.model|ucfirst}>();
        $select = $model->select()
            ->where("<{$item.OPTION.key}> = ?", $default)
            ;
        $vos = $model->fetchAll($select);
        $vo = current($vos);
        $default = $vo->get("<{$item.OPTION.val}>");
<{else if $item.OPTION.ref_conf}>
        $array = $this->_registry["modconf"]
                    -><{$module_name}>
                    -><{$item.OPTION.section}>
                    ->toArray();
        $default = $array[$default];
<{else if $item.OPTION}>
        <{foreach from=$item.OPTION key=key item=val}>
            $array[<{$key}>] = "<{$val}>";
        <{/foreach}>
        $default = $array[$default];
<{else}>
        $array = array(
            0 => "no",
            1 => "yes",
        );
        $default = $array[$default];
<{/if}>
<{/if}>

        return $default;
    }
<{/foreach}>

}
