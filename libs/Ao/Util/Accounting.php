<?php
/**
 * Accounting Utility class
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
 * @author        aotake <aotake@bmath.org>
 */

class Ao_Util_Accounting 
{
    /**
     * 消費税取得
     *
     * 税抜価格と税率を渡して諸費税を計算する
     * 第三引数で端数を切り上げるか切り下げるかの指定をする（現在未使用）。
     * デフォルトは floor() で処理する。
     *
     * @param int $price 税抜き金額
     * @param int $ratio 消費税率(%)
     * @param int $flag  端数処理
     * @return int
     */
    public static function tax($price, $ratio = 5, $flag = 0)
    {
        $tax = $price * 0.01 * $ratio;

        switch($flag){
        case 0:
        default:
            $tax = floor($tax);
            break;
        }
        return $tax;
    }
}
