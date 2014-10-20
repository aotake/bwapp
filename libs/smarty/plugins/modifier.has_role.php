<?php
/**
 * Smarty plugin
 * 
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty has_role modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     has_role<br>
 * Purpose:  has_role words in the string
 *
 * 利用例：
 *
 * <{if $_userInfo|has_role:"agent"}>
 * <li><a href="<{$siteconf.root_url}>/agent/">小売店購入リスト</a></li>
 * <{/if}>
 * 
 * @link 
 * @author Monte Ohrt <monte at ohrt dot com> 
 * @param string $ 
 * @return string 
 */
function smarty_modifier_has_role($userInfo, $rolename = "member")
{ 
    if( ! ($userInfo instanceof Ao_Vo_Abstract) ){
        return false;
    }
    if( !is_array($userInfo->get("role")) ){
        return false;
    }
    if( $rolename == "" ){
        return false;
    }

    foreach( $userInfo->get("role") as $role_vo ){
        if( $role_vo->get("name") == $rolename ){
            $flag = true;
            break;
        }
    }
    return $flag;
} 

?>
