<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty to_pascal modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     to_pascal<br>
 * Purpose:  simple search/to_pascal
 * 
 * @author aotake<aotake at bmath dot org> 
 * @author aotake
 * @param string $ 
 * @return string 
 */
function smarty_modifier_to_pascal($string)
{
    $delim = "_";
    $str  = strtolower($string);
    $strs = explode($delim, $str);
    $strs = array_map("ucfirst", $strs);
    return implode("", $strs);
} 

?>
