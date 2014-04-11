<?php
/**
 * Default Error Controller
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
 * @package       Ao.modules.admin.Controller
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author     Takeshi Aoyama (aotake@bmath.org)
 */
$CtrName = str_replace("Controller.php", "", basename(__FILE__));
$custom_file = dirname(__FILE__)."/Custom/".$CtrName."CustomController.php";
$base_file = dirname(__FILE__)."/Base/".$CtrName."BaseController.php";
if(file_exists($custom_file)){
    require_once $custom_file;
    $parentClass = $CtrName."CustomController";
} else {
    require_once $base_file;
    $parentClass = $CtrName."BaseController";
}

eval('
class '.$CtrName.'Controller extends '.$parentClass.'
{
}
');
