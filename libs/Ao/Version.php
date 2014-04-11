<?php
/**
 * Version
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
 * @package       Ao
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

final class Ao_Version
{
    const VERSION = '0.1.0';
    protected static $_lastestVersion;

    public static function compareVersion($version)
    {
        $version = strtolower($version);
        $version = preg_replace('/(\d)pr(\d?)/', '$1a$2', $version);
        return version_compare($version, strtolower(self::VERSION));
    }

    public static function getLatest()
    {
        $root_url = Zend_Registry::get("config")->site->root_url;
        if (null === self::$_lastestVersion) {
            self::$_lastestVersion = 'not available';

            $handle = fopen($root_url.'/version', 'r');
            if (false !== $handle) {
                self::$_lastestVersion = stream_get_contents($handle);
                fclose($handle);
            }
        }

        return self::$_lastestVersion;
    }
}
