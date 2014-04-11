<?php
/**
 * <{$TableName}>バリデータ
 *
 * @category Validator
 * @package Validator_Form_<{$ModuleName}>_Module_Bmathlog
 */
class <{$ModuleName}>_Form_Validator_<{$TableName}> extends Ao_Util_Validator
{
    /**
     * 呼出元コントローラオブジェクト
     */
    protected $_controller;
    /**
     * コンストラクタ
     */
    public function __construct(&$controller)
    {
        $this->_controller = $controller;
    }
    /**
     * リスト形式のフォームチェック
     *
     * @param string $key データを保存している配列名
     * @return array エラーメッセージの配列
     */
    public function listInput($key = "item")
    {
        $req = $this->_controller->getRequest();
        $params = $req->getParams();
        $lists = $params[$key];
        $_POST_ORIG = $_POST; // オリジナルの _POST データのバックアップ
        foreach($lists as $item)
        {
            $_POST = $item; // TODO: この仕様は変えたい
            $id = $item["id"];
            $errors[$id] = $this->input();
        }
        $total_error_num = 0;
        if($errors){
            foreach($errors as $e){
                $total_error_num += $e["error_num"];
            }
        }
        $errors["total_error_num"] = $total_error_num;
        // バックアップした_POST を元に戻す
        $_POST = $_POST_ORIG;
        return $errors;
    }
    /**
     * レコード単位のフォームチェック
     *
     * @param void
     * @return array エラーメッセージの配列
     */
    public function input(){
        // {{{
        if( getenv("REQUEST_METHOD") != "POST" ){
                return false;
        }
        $error = array();
        // 必須入力チェック
<{foreach from=$tableInfo item=item}>
<{if $item.NULLABLE || $item.COLUMN_NAME == "id"}>
        //$error['<{$item.COLUMN_NAME}>']['required'] = $this->postRequired('<{$item.COLUMN_NAME}>');
<{else}>
        $error['<{$item.COLUMN_NAME}>']['required'] = $this->postRequired('<{$item.COLUMN_NAME}>');
<{/if}>
<{/foreach}>

        // フォーマットチェック
<{foreach from=$tableInfo item=item}>
<{if $item.DATA_TYPE == "int" || $item.DATA_TYPE == "tinyint"}>
        $error['<{$item.COLUMN_NAME}>']['int'] = $this->postIsInteger('<{$item.COLUMN_NAME}>');
<{/if}>
<{/foreach}>

        // ホワイトリストでチェック
        //$wlist = array("AH","IT","KS","KT");
        //$error['sign']['unknown'] = $this->postWhiteListCheck('sign', $wlist);

        // もし入力されていたらチェックする項目
        //if( $this->postExistVal( 'address' ) ){
        //    $error['address']['length'] = $this->postStrlenMinMax('address', 0, 32);
        //}

        //$error["global"]["registed"] = $this->isRegisted();

        $res['error']     = $error;
        $res['error_num'] = $this->countError( $error );
        return $res;
        // }}}
    }
    /**
     * 登録済みチェック
     * 
     * 登録済みだったらメッセージを返す。未登録なら null を返す (sample)
     */
    public function isRegisted()
    {
        $req = $this->_controller->getRequest();
        $info_id = $req->getPost("info_id");
        $company = $req->getPost("company");
        $name = $req->getPost("name");
        //$email = $req->getPost("email");

        $entry = new Seminar_Model_Entry();
        $select = $entry->select();
        $select->where("delete_flag = 0 or delete_flag is null")
            ->where("info_id = ?", $info_id)
            ->where("company = ?", $company)
            ->where("name = ?", $name)
            //->where("email = ?", $email)
            ;
        $vos = $entry->fetchAll($select);

        $res = null;
        if($vos){
            $res = "既に登録済みです";
        }
        return $res;
    }

}
