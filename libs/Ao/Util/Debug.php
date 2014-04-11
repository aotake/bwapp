<?php
/**
 * Debug Utility
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

class Ao_Util_Debug
{
    static public function trace(&$trace, $content, $php_message = null)
    {
        $trace_arr = array();
        foreach($trace as $item){
            if(count($item["args"]) > 0){
                $_args = array();
                foreach($item["args"] as $i => $_arg){
                    if(is_string($_arg)){
                        $_args[] = $_arg;
                    } else {
                        if($_arg){
                            $_args[] = get_class($_arg)." Object";
                        } else {
                            $_args[] = "null Object";
                        }
                    }
                }
                if(count($_args)){
                    $arg = implode(", ",$_args);
                } else {
                    $arg = null;
                }
            } else {
                $arg = null;
            }
            $trace_arr[] = array(
                "file" => $item["file"],
                "line" => $item["line"],
                "function" => $item["function"],
                "class" => $item["class"],
                "type" => $item["type"],
                "args" => $arg
            );
        }

print <<<___HTML___
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>Error</title>
</head>
<body>
<style type="text/css">
div#exception_debug{
    padding: 10px;
}
h2#exception {
    border-left: 20px solid #aa0000;
    border-bottom: 1px solid #aa0000;
    background-color: #fdd;
    padding-left: 10px;
}
h3.exception {
    border-bottom: 1px solid #aa0000;
}
div#exception_message {
    text-align: center;
    padding: 50px 20px;
    font-weight: bold;
    margin: 0 10px 20px 10px;
    color: #f00;
    font-size: 120%;
    border: 1px solid #f66;
}
div#php_message{
    padding:10px;
}

#err {
    padding: 0px;
}
#err th, #err td {
    padding: 2px;
    margin:0;
}
#err th {
    padding: 5px;
    color: #fff;
    background-color: #a00;
}
.traceitem {
    width: 100%;
}
#err td .traceitem th {
    width: 150px;
    padding: 2px;
    color: 000;
    text-align: left;
    background-color: #fcc;
}
.traceitem td {
    padding: 2px;
    text-align: left;
    background-color: #fee;
}
</style>

<h2 id="exception">Error</h2>
<div id="exception_message">
    <div>{$content}</div>
</div>

<h3 class="exception">Warning, Notice ....</h3>
<div id="php_message"> {$php_message} </div>

<div id="exception_debug">
<h3 class="exception">Deug Information</h3>
<table id="err">
___HTML___;
    foreach($trace_arr as $i => $item){
        print "<tr>\n";
        print "<th>$i</th>\n";
        print "<td>\n";
        print "<table class='traceitem'>\n";
        print "<tr><th>file</th><td>".$item["file"]."</td></tr>\n";
        print "<tr><th>line</th><td>".$item["line"]."</td></tr>\n";
        print "<tr><th>Function</th><td>";
        if($item["type"]){
            print $item["class"].$item["type"].$item["function"]."()";
        } else {
            print $item["function"]."()";
        }
        print "</td></tr>\n";
        print "<tr><th>Argument</th><td>".$item["args"]."</td></tr>\n";
/*
        ob_start();
        print_r($item);
        $_trace = ob_get_contents();
        $_trace = nl2br(htmlspecialchars($_trace));
        ob_end_clean();
*/
        print "</table>";
        print "</td>";
        print "</tr>";
    }
print <<<___HTML___
</table>
</body>
</html>
___HTML___;

    }
}
