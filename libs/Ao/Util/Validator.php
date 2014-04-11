<?php
/**
 * Post Request Validator
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
 * @package       Ao.Util
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

class Ao_Util_Validator {
    const ENC = "UTF-8";

    // Validator を置き換えるまでの処置
    function zendParamsToPost()
    {
        if(isset($this->_controller)){
            $req = $this->_controller->getRequest();
            foreach($req->getParams() as $key => $val){
                $_POST[$key] = $val;
            }            
        }
    }

	function email( $email = null ){
		if( !$email ){
			return false;
		}
		if(!preg_match("/^([a-z]|[0-9]|\.|\?|\/|\+|-|_)+@([a-z,0-9,_,-]+)\.([a-z,0-9,_,-]+)/i",$email)){
		        return false;
		}
		return true;
	}

	//
	// 禁止ワードが含まれていれば true を返す
	//
	function existsBadWordIn( $text ){
		// 禁止ワードを 「'」で括って「,」で区切って並べる
		$badwords = array(
			'エッチ','死ね','馬鹿','殺す','自殺','死ぬ','馬鹿','うざい','うんこ','ちんこ','あほ',
			'抹消','喧嘩','死んで','まんこ','ま○こ','ちんかす','ち○こ','チンコ','マンコ','アナル',
			'パイ毛','おっぱい','乳首','ちん毛','セックス','死','殺','ばか','バカ','アホ','デブ',
			'はげ','ころす','しね','じさつ','ぶす','お前','おまえ','オマエ','ｵﾏｴ','地獄','じごく',
			'てめー','ちび','チビ','上等','去れ','しんで','きもい','きもすぎ','気もい','気持ち悪い'
		);

		$count  = count( $badwords );
		$exists = false;
		foreach( $badwords as $word ){
			if(stristr($text, $word)){
				$exists = true;
				break;
			}
		}
		return $exists;
	}

	function postRequired( $postKey = null, $errmsg = null ){
		if( isset( $_POST[ $postKey ] ) && $_POST[ $postKey ] !== ""  ){
			return null;
		} else {
			if( !$errmsg ){
				 $errmsg = "必須項目です";
			}
			return $errmsg;
		}
	}

	// 文字列の長さを比較する
	function postStrlen( $postKey = null, $length = 0, $errmsg = null ){
		if( isset( $_POST[ $postKey ] ) && mb_strlen($_POST[ $postKey ], self::ENC) == $length ){
			return null;
		} else {
			if( !$errmsg ){
				$errmsg = "$length 文字でなければなりません。";
			}
			return $errmsg;
		}
	}

	// 文字列の最小、最大長をチェックする
	function postStrlenMinMax( $postKey = null, $length_min = 0, $length_max = 0, $errmsg = null ){
		$flag    = false;
		$_errmsg = array();
		if( isset( $_POST[ $postKey ] ) && mb_strlen($_POST[ $postKey ], self::ENC) < $length_min ){
			$_errmsg[] = "$length_min 文字以上 $length_max 文字以下で入力して下さい";
			$flag = true;
		} 
		elseif( isset( $_POST[ $postKey ] ) && $length_max > 0 && mb_strlen($_POST[ $postKey ], self::ENC) > $length_max ){
			$_errmsg[] = "$length_min 文字以上 $length_max 文字以下で入力して下さい";
			$flag = true;
		}
		if( !empty( $_errmsg ) ){
			$errmsg = implode( "<br>", $_errmsg );
		}

		if( $flag ){
			return $errmsg;
		} else {
			return null;
		}
	}

	// 選択されている項目が $value の値（デフォルトは null）のときエラー
	function postSelected( $postKey = null, $value = null, $errmsg = null ){
		if( $_POST[ $postKey ] == $value ){
			if( !$errmsg ){
				$errmsg = "選択されていません。";
			}
			return $errmsg;
		} else {
			return null;
		}
	}

    /** * マッチしたらエラー */
    function postInvalidRegexFormat( $postKey, $pattern, $errmsg = null){
		if( preg_match("$pattern", $_POST[ $postKey ]) ){
			if( !$errmsg ){
				$errmsg = "書式が正しくないようです。";
			}
			return $errmsg;
		} else {
			return null;
        }
    }
    /** * マッチしなかったらエラー */
    function postRegexFormat( $postKey, $pattern, $errmsg = null){
		if( !preg_match("$pattern", $_POST[ $postKey ]) ){
			if( !$errmsg ){
				$errmsg = "書式が正しくないようです。";
			}
			return $errmsg;
		} else {
			return null;
        }
    }

	function postEmailFormat( $postKey = null, $errmsg = null ){
		if( !preg_match("/^([a-z]|[0-9]|\.|\?|\/|\+|-|_)+@([a-z,0-9,_,-]+)\.([a-z,0-9,_,-]+)/i", $_POST[ $postKey ]) ){
			if( !$errmsg ){
				$errmsg = "書式が正しくないようです。";
			}
			return $errmsg;
		} else {
			return null;
		}
	}

	function postDateFormat( $postKey = null, $errmsg = null ){
		if( !preg_match("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/i", $_POST[ $postKey ]) ){
			if( !$errmsg ){
				$errmsg = "「日付」の書式が正しく入力されていないようです。";
			}
			return $errmsg;
		} else {
			return null;
		}
	}

	function postTelFormat( $postKey = null, $errmsg = null ){
		if( !preg_match("/^0([0-9]+)$/i", preg_replace("/-/", "",$_POST[ $postKey ])) ){
			if( !$errmsg ) {
				$errmsg = "書式が正しくないようです。";
			}
			return $errmsg;
		} else {
			return null;
		}
	}

	function postZipFormat( $postKey = null, $errmsg = null ){
		if( !preg_match("/^([0-9]{7})$/i", $_POST[ $postKey ]) ){
			if( !$errmsg ) {
				$errmsg = "数字7桁で記入して下さい。";
			}
			return $errmsg;
		} else {
			return null;
		}
	}

	function postIsInteger( $postKey = null ){
		$val = isset( $_POST[ $postKey ] ) ? trim($_POST[ $postKey ] ) : null;
        if($val){
            if(!preg_match("/^[0-9][0-9]*$/", $val) && !preg_match("/^\-[0-9][0-9]*$/", $val)){
                return "半角数字で記入して下さい";
            }
        }
        return null;
	}

	function postWhiteListCheck( $postKey = null, $whitelist = array() ){
		$res = in_array( $_POST[ $postKey ], $whitelist );
		if( !$res ){
			return $_POST[ $postKey ]."は登録情報に含まれてません";
		}
		return 0;
	}

	function postExistKey( $postKey ){
		return isset( $_POST[ $postKey ] );
	}

	function postExistVal( $postKey ){
        return array_key_exists($postKey, $_POST) && $_POST[ $postKey ] != "";
		//return (isset( $_POST[ $postKey ] ) && $_POST[ $postKey ] != "" );
	}

	// エラー配列を参照してエラー数を数える
	//	エラー配列は
	//		array(
	//			'post されるキー' =>
	//				array(
	//					'required' => "必須項目エラーメッセージ"
	//					'length'   => "長さエラーメッセージ"
	//					'integer'  => "数値じゃないというエラーメッセージ"
	//					'format'   => "フォーマットエラーメッセージ"
	//					'selected' => "選択エラーメッセージ"
	//				)
	//		)
	function countError( $error = array() ){
                $count = 0;
                foreach( $error as $key => $attr ){
                        foreach( $attr as $val ){
                                if( !empty( $val ) ){
                                        $count++;
                                }
                        }
                }
                return $count;
        }
}
