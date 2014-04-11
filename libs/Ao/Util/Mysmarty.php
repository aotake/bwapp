<?php
/**
 * Smarty wrapper
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
// ツールで利用する Smarty, Zendでは使わない
class Ao_Util_Mysmarty extends Smarty
{
    public function __construct($param = null)
    {
        parent::__construct();
        if($param){
            foreach($param as $key => $val){
                $this->$key = $val;
            }
        }
    }

    // Smarty3 にしたら $this->_smarty->fetch で以下のようなエラーが出る
    // ことがある
    //
    // Fatal error: Call to a member function setScriptPath() on a non-object in /Users/web/myproject/libs/Ao/Controller/Action.php on line 55
    // 
    // おそらく、Smarty() のオブジェクトにないからではないか、という結論
    public function setScriptPath($path)
    {
        if(is_readable($path)){
            $this->template_dir = $path;
            return ;
        }
        throw new Exception("無効なパスが指定されました: $path");
    }
}
