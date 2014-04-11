<?php
/**
 * <{$MModuleName}> <{$ManagerName}>マネージャクラス
 *
 * @category Manager
 * @package Manager_<{$ManagerName}>_Module_Bmathlog
 */
class <{$ModuleName}>_Manager_<{$ManagerName}> extends Manager
{
    /**
     * 呼出元コントローラオブジェクト
     */
    protected $controller;
    /**
     * リクエストオブジェクト
     */
    protected $request;
    /**
     * コンストラクタ
     *
     * @param $controller
     * @return void
     */
    public function __construct(&$controller)
    {
        $this->controller = $controller;
        $this->request = $this->controller->getRequest();
        $this->auth = new Zend_Session_Namespace("auth");
<{foreach from=$UseModels item=item}>
        $this->m<{$item.tableName}>= new <{$item.className}>();
<{/foreach}>
    }

<{foreach from=$UseModels item=item}>
    /**
     * Vo取得
     *
     * 引数があれば値を埋めた Vo を返す。なければ空の Vo を返す。
     *
     * @param array $param
     * @return <{$ModuleName}>_Vo_<{$item.tableName}>
     */
    public function get<{$item.tableName}>Vo($param = array())
    {
        $model =& $this->m<{$item.tableName}>;
        $vo = $model->getVo($param);
        return $vo;
    }
    /**
     * Voの配列取得
     *
     * 引数があれば対応する Vo を唯一の要素とする配列を返す。引数がなければ
     * 全てのレコードの Vo を要素とする配列を返す。
     *
     * @param int $id 主キー
     * @return array <{$ModuleName}>_Vo_<{$item.tableName}>の配列
     */
    public function get<{$item.tableName}>($id = null)
    {
        $model =& $this->m<{$item.tableName}>;
        $select = $model->select();
        $select->where("delete_flag = 0 or delete_flag is null");
        $select->order("id asc");
        if($id){
            $select->where("id = ?", $id);
        }
        return $model->fetchAll($select);
    }
    /**
     * ページ単位のVoの配列取得
     *
     * 引数があれば対応する Vo を唯一の要素とする配列を返す。引数がなければ
     * 全てのレコードの Vo を要素とする配列を返す。
     *
     * @param int $limit １ページの表示件数
     * @param int $offset 表示オフセット
     * @param array $orders ソート条件の配列
     * @param string $keyword キーワード文字列
     * @return array <{$ModuleName}>_Vo_<{$item.tableName}>の配列
     */
    public function getPage($limit = 20, $offset = 0, $orders = array("id asc"), $keyword = null)
    {
        $model =& $this->m<{$item.tableName}>;
        $select = $model->select();
        $select->where("delete_flag = 0 or delete_flag is null");
        // ソート条件の正規化(?)
        if($orders == null){
            $orders = array("id asc");
        } else if (is_string($orders)) {
            $orders = array($orders);
        }
        // ソート条件登録
        foreach($orders as $order){
            $select->order($order);
        }
        if($keyword){
            //$keyword == Ao_Util_TextSanitizer::sanitize($keyword);
            $m   = $this->controller->getRequest()->getModuleName();
            $reg = Zend_Registry::getInstance();
            if(isset($reg["modconf"]->$m)
                && isset($reg["modconf"]->$m->search)
                && isset($reg["modconf"]->$m->search->target_columns)
            ){
                $target_columns = $reg["modconf"]->$m->search->target_columns;
                $target_columns = explode(",", $target_columns);
                foreach($target_columns as $col){
                    $cond[] = $col." like '%".$keyword."%'";
                }   
                $select->where(implode(" or ", $cond));
            }
        }
        $select->limit($limit, $offset);
        return $model->fetchAll($select);
    }
    /**
     * データ件数
     *
     * キーワードが指定されているときは、キーワードを含むデータの件数を、
     * キーワードが無い時は全てのデータ件数を返す。
     *
     * @param string $keyword キーワード文字列
     * @return int 登録データ件数
     */
    public function getCount($keyword = null)
    {
        $count = 0;
        $model =& $this->m<{$item.tableName}>;
        $select = $model->select()
            ->setIntegrityCheck(false)
            ->from($this->m<{$item.tableName}>->name(), 'count(*) as count');
        $select->where("delete_flag = 0 or delete_flag is null");
        if($keyword){
            //$keyword == Ao_Util_TextSanitizer::sanitize($keyword);
            $m   = $this->controller->getRequest()->getModuleName();
            $reg = Zend_Registry::getInstance();
            if(isset($reg["modconf"]->$m)
                && isset($reg["modconf"]->$m->search)
                && isset($reg["modconf"]->$m->search->target_columns)
            ){
                $target_columns = $reg["modconf"]->$m->search->target_columns;
                $target_columns = explode(",", $target_columns);
                foreach($target_columns as $col){
                    $cond[] = $col." like '%".$keyword."%'";
                }   
                $select->where(implode(" or ", $cond));
            }
        }
        $res = $model->fetchAll($select,1);
        if($res){
            $count = $res[0]["count"];
        }
        return $count;
    }
    /**
     * 保存
     *
     * $vo に主キーあれば更新、無ければ新規登録をする。
     * 保存に成功した時は主キー値を返す。失敗時は例外を送出する。
     *
     * @param <{$ModuleName}>_Vo_<{$ManagerName}> $vo Voオブジェクト
     * @return int 主キー
     */
    public function save<{$item.tableName}>($vo)
    {
        $model =& $this->m<{$item.tableName}>;
        $model->getAdapter()->beginTransaction();
        try{
            $id = $model->save($vo);
            $model->getAdapter()->commit(); 
        } catch(Exception $e){
            $model->getAdapter()->rollback(); 
            print $e->getMessage();
            throw $e;
        }
        return $id;
    }
    /**
     * 削除
     *
     * $id で指定したレコードをデータベースから削除する。
     * 実削除か論理削除かは Model の実装による。
     * エラー時は例外を送出する。
     *
     * @param int $id 主キー
     * @return boolean
     */
    public function delete<{$item.tableName}>($id = null)
    {
        $req = $this->controller->getRequest();
        $id = $req->getParam("id");
        if($id == ""){
            throw new Zend_Exception("ID が取得できませんでした");
        }
        return $this->m<{$item.tableName}>->del($id);
    }

<{/foreach}>

    /**
     * 日付・整数変換
     *
     * 日付指定のデータを元に日付をUNIX_TIMESTAMP表現に変換する(開始)
     */
    public function convYmdToInt(&$param, $colname)
    {
        $y = (int)$param[$colname."_y"];
        $m = (int)$param[$colname."_m"];
        $d = (int)$param[$colname."_d"];
        $h = (int)$param[$colname."_h"];
        $i = (int)$param[$colname."_i"];
        $s = 0; //(int)$param[$colname."_s"];
        // 日付が正しく指定されていなければ、時刻が選択されていても 0 とする
        if($y == "--" || $m == "--" || $d == "--"){
            $param[$colname] = 0;
        } else {
            $param[$colname] = mktime($h, $i, $s, $m, $d, $y);
        }
    }
    /**
     * 整数・日付変換
     *
     * UNIX_TIMESTAMP を日付データに変換する
     */
    public function convIntToYmd(&$vo, $colname)
    {
        $utime = $vo->get($colname);
        if($utime == 0){
            $vo->set($colname."_y", "--");
            $vo->set($colname."_m", "--");
            $vo->set($colname."_d", "--");
            $vo->set($colname."_h", "--");
            $vo->set($colname."_i", "--");
        } else {
            $vo->set($colname."_y", date("Y", $vo->get($colname)));
            $vo->set($colname."_m", date("m", $vo->get($colname)));
            $vo->set($colname."_d", date("d", $vo->get($colname)));
            $vo->set($colname."_h", date("H", $vo->get($colname)));
            $vo->set($colname."_i", date("i", $vo->get($colname)));
        }
    }
/* sample of left join
    public function getRolesByUid($rid = null)
    {
        $model = new Model_Role();
        $model_ur= new Model_RoleRole();
        $select = $model->select();
        $select->setIntegrityCheck(false)
            ->from(array("r" => $model->name()), array("*"))
            ->joinLeft(
                array("ur" => $model_ur->name()),
                "r.rid = ur.rid",
                array("*"))
            ->where("ur.rid = ?", (int)$rid)
            ->order("r.rid asc")
            ;
        $res = $model->fetchAll($select, true);
        if($res){
            return $this->_assignCommonVos($res);
        } else {
            return null;
        }
    }
*/

}
