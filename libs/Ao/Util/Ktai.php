<?php
/**
 * Mobile Carrier Utility
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
class Ao_Util_Ktai 
{
    const CARRIER_PC = 0;
    const CARRIER_DOCOMO = 1;
    const CARRIER_EZWEB = 2;
    const CARRIER_SOFTBANK = 3;

    static public function getCarrierCode()
    {
        $_mobile_carrier = null;

        //UA判別正規表現と判別結果の定義
        $uaList = array(
            array(
                'regexp'  => '!^DoCoMo!',
                'carrier' => self::CARRIER_DOCOMO,
            ),
            array(
                'regexp'  => '!^KDDI-!',
                'carrier' => self::CARRIER_EZWEB,
            ),
            array(
                'regexp'  => '!^UP\.Browser!',
                'carrier' => self::CARRIER_EZWEB,
            ),
            array(
                'regexp'  => '!^SoftBank!',
                'carrier' => self::CARRIER_SOFTBANK,
            ),
            array(
                'regexp'  => '!^Vodafone!',
                'carrier' => self::CARRIER_SOFTBANK,
            ),
            array(
                'regexp'  => '!^J-PHONE!',
                'carrier' => self::CARRIER_SOFTBANK,
            ),
            array(
                'regexp'  => '!^MOT-!',
                'carrier' => self::CARRIER_SOFTBANK,
            ),
            array(
                'regexp'  => '!^Semulator!',
                'carrier' => self::CARRIER_SOFTBANK,
            ),
            array(
                'regexp'  => '!^Vemulator!',
                'carrier' => self::CARRIER_SOFTBANK,
            ),
            array(
                'regexp'  => '!^J-EMULATOR!',
                'carrier' => self::CARRIER_SOFTBANK,
            ),
            array(
                'regexp'  => '!^MOTEMULATOR!',
                'carrier' => self::CARRIER_SOFTBANK,
            ),
        );
        
        if(isset($_SERVER["HTTP_USER_AGENT"])){
            $ua = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $ua = "pc";
        }
        foreach ($uaList as $item) {
            if (preg_match($item['regexp'], $ua)) {
                $_mobile_carrier = $item['carrier'];
                break;
            }
        }
        if ($_mobile_carrier == null) {
            // PCやその他のUAでアクセスされたとき
            $_mobile_carrier = self::CARRIER_PC;
        }
        return $_mobile_carrier;
    }

    public function getDoctype($carrier = null)
    {
        if($carrier == null){
            $carrier = self::getCarrierCode();
        }
        switch($carrier){
        case self::CARRIER_DOCOMO:
            $doctype = '<!DOCTYPE html PUBLIC "-//i-mode group (ja)//DTD XHTML i-XHTML(Locale/Ver.=ja/2.3) 1.0//EN" "i-xhtml_4ja_10.dtd">';
            break;
        case self::CARRIER_EZWEB:
            $doctype = '<!DOCTYPE html PUBLIC "-//OPENWAVE//DTD XHTML 1.0//EN" "http://www.openwave.com/DTD/xhtml-basic.dtd">';
            break;
        case self::CARRIER_SOFTBANK:
            $doctype = '<!DOCTYPE html PUBLIC "-//J-PHONE//DTD XHTML Basic 1.0 Plus//EN" "xhtml-basic10-plus.dtd">';
            break;
        default:
            $doctype = '<!DOCTYPE html PUBLIC "-//i-mode group (ja)//DTD XHTML i-XHTML(Locale/Ver.=ja/2.3) 1.0//EN" "i-xhtml_4ja_10.dtd">';
            break;
        }
        return $doctype;
    }
}
