<?php
/**
 * Command Option Utility
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 2001-2007 Gregory Beaver
 *
 * LICENSE:
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 2.1 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @copyright     Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
 * @link          http://bmath.jp
 * @package       Ao.Util
 * @since
 * @license       http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author        aotake <aotake@bmath.org>
 */
class Ao_Util_CmdTool
{
    /**
     * コマンドラインオプション
     */
    private $options;
    /**
     * creates an array $this->phpDocOptions and sets program options in it.
     * Array is in the format of:
     * <pre>
     * [filename][tag][] = "f";
     * [filename][tag][] = "-file";
     * [filename][desc] "name of file to parse"
     * </pre>
     */
    public function __construct()
    {
        $this->options = array();
    }
    public function setOptions($opt)
    {
        if(!is_array($opt)){
            return false;
        }
        foreach($opt as $name => $o){
            $this->options[$name]["tag"] = $o["tag"];
            $this->options[$name]["desc"] = $o["desc"];
        }
        return true;
    }
    /**
     * create the help message for display on the command-line
     * @return string a string containing a help message
     */
    public function displayHelpMsg()
    {
        unset($ret);
        $ret = "\n";
        foreach($this->options as $data)
        {
            unset($tag);
            $tag = "";
            if (isset($data['tag']))
            {
                if (is_array($data['tag'])) {
                    foreach($data['tag'] as $param) {
                        $tag .= "$param    ";
                    }
                }
                $taglen = 34;
                $outputwidth = 79;
                $tagspace = str_repeat(" ",$taglen);
                $tmp = "  ".trim($tag).$tagspace;
                $tmp = substr($tmp,0,$taglen);
                $d = wordwrap(ltrim($data['desc']),($outputwidth-$taglen));
                $dt = explode("\n",$d);
                $dt[0] = $tmp .$dt[0];
                for($i=1;$i<count($dt);$i++)
                {
                    $dt[$i] = $tagspace.$dt[$i];
                }
                $ret .= implode("\n",$dt)."\n\n";
            }
        }
        //$ret .= "\n".wordwrap($data['message'],$outputwidth)."\n";
        return $ret; 
    }
    /**
     * Parses $_SERVER['argv'] and creates a setup array
     * @return array a setup array
     * @global array command-line arguments
     * @todo replace with Console_* ?
     */
    function parseArgv()
    {
        global $argv;

        // defaults for setting
        $setting['ignoresymlinks'] = 'off';

        $valnext = "junk";
        $data = array();
        if(isset($argv) && is_array($argv))
        {
            foreach ($argv as $cmd)
            {
                if ($cmd == '--') {
                    continue;
                }
                if ($cmd == '-h' || $cmd == '--help')
                {
                    echo $this->displayHelpMsg();
                    die();
                }

                // at first, set the arg value as if we
                // already know it's formatted normally, e.g.
                //    -q on
                $setting[$valnext] = $cmd;

                if (isset($data['type']) && $data['type'] == 'set') {

                    if ($valnext !== 'junk' && strpos(trim($cmd),'-') === 0) {
                        // if valnext isn't 'junk' (i.e it was an arg option) 
                        // then the first arg needs an implicit "" as its value, e.g.
                        //     ... -q -pp ...  ===>  ... -q '' -pp ... 
                        $setting[$valnext] = '';

                    } else if (!in_array(strtolower($cmd), $data['validvalues'], true)) {
                        // the arg value is not a valid value
                        addErrorDie(PDERROR_INVALID_VALUES, $valnext, $cmd,
                            '(' . implode(', ', $data['validvalues']) . ')');
                    }
                }

                foreach( $this->options as $name => $data )
                {
                    if (!empty($data['tag']))
                    {
                        if (in_array($cmd,$data['tag']))
                        {
                            $valnext = $name;
                            break;
                        } 
                        else
                        {
                            $valnext = "junk";
                        }
                    }
                }

                if ($valnext == 'junk' && (strpos(trim($cmd),'-') === 0)) {
                    // this indicates the last arg of the command 
                    // is an arg option (-) that was preceded by unrecognized "junk"
                    //addErrorDie(PDERROR_UNKNOWN_COMMANDLINE,$cmd);
                    throw new Zend_Exception("Unknown command line option: $cmd");

                } else if ($valnext != 'junk' && (strpos(trim($cmd),'-') === 0)) {
                    // this indicates the last arg of the command 
                    // is an arg option (-) without an arg value
                    
                    // add an empty arg "value" for this arg "option"
                    $setting[$valnext] = '';
                }


            }
        } else
        {
            echo "Please use php-cli.exe in windows, or set register_argc_argv On";
            die;
        }
        /* $setting will always have at least 3 elements
        [ignoresymlinks] => 'off'
         */
        if (count($setting) < 3) {
            echo $this->displayhelpMsg();
            die();
        }

        return $setting;
    }
}

