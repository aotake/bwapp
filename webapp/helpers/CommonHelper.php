<?php
/**
 * サイト内共通ヘルパー
 * 
 * My_CommonHelper
 *
 * どの画面でもだいたい共通で必要となるパラメータ値の取得などに使う。
 * 結果は配列のキーを Smarty 変数、値を Smarty 変数に埋め込む値として持たせる。
 * 内部変数 $data に配列を保持し、execute() メソッドは init() 時に指定された
 * メソッドの結果 $this->data を返す。
 *
 * 結果は $this->data 配列のキーを Smarty 変数名として使うことで表示できる。
 * see: Ao_Controller_Action::postDispatch()
 */
class My_CommonHelper extends Zend_Controller_Action_Helper_Abstract
{
    private $data;
    private $execute_methods;

    public function init()
    {
        $this->data = array();

        // execute() で実行するメソッド名を配列で指定する
        $this->execute_methods = array(
            //"getCartAmount",
            //"getTweet",
        );
    }

    public function execute() {
        foreach( $this->execute_methods as $method ){
            $this->$method();
        }
        return $this->data;
    }

    // //--------------- サンプルメソッド --------------------
    // // カート内アイテム数
    // //
    // // Smarty で <{$cart_amount}> として参照することを想定
    // //
    // private function getCartAmount()
    // {
    //     $manager = new Cart_Manager_Data($this->controller);
    //     $val = $manager->amount(); // 注文総数
    //     $this->data["cart_amount"] = (int)$val;
    // }

    // // Twitter キャッシュデータ取得
    // //
    // // Smarty で <{$tweets}> として参照することを想定
    // //
    // private function getTweet()
    // {
    //     $tw_array = unserialize( file_get_contents( Zend_Registry::get("webappDir")."/include/tw_serialize.php" ) );
    //     $tweets = array();
    //     for($i = 0; $i < 5; $i++ ){
    //         $tweets[] = $tw_array[ $i ][ "text" ];
    //     }
    //     $this->data["tweets"] = $tweets;
    // }

}

