<?php
/**
 * Smarty plugin
 * 
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty to_timestr modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     to_timestr<br>
 * Purpose:  format datestamps via strftime<br>
 * Input:<br>
 *          - int: sec
 *          - format: 1 - 6
 *          - zeropadding: true or false
 * 
 * @author aotake<aotake at bmath dot org> 
 * @param string $ 
 * @param int $ 
 * @param bool $ 
 * @return string |void
 */
function smarty_modifier_to_timestr($string, $format = 1, $zeropadding = false)
{
    if ($string != '') {
        $string = Ao_Util_Time::convSecToTimeStr($string, $format, $zeropadding);
    }
    return $string;
} 

?>
