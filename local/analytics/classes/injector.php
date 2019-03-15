<?php
// This file is part of the Local Analytics plugin for Moodle
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     local_analytics
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Class injector
 *
 * @package     local_analytics
 * @author      David Bezemer <info@davidbezemer.nl>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   David Bezemer <info@davidbezemer.nl>, www.davidbezemer.nl
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class injector {
    /** @var bool */
    private static $injected = false;

    public static function inject() {
        if (self::$injected) {
            return;
        }
        self::$injected = true;

        $engine = null;

        $analytics = get_config('local_analytics', 'analytics');
        $analyticstypes = array('guniversal', 'ganalytics', 'piwik');
        
        foreach ($analyticstypes as $type) {
            $enabled = get_config('local_analytics', $type);
            if ($enabled) {
                $classname = "\\local_analytics\\api\\{$type}";
                if (!class_exists($classname, true)) {
                    debugging("Local Analytics Module: Analytics setting '{$type}' doesn't map to a class name.");
                    return;
                }

                $engine = new $classname;
                $engine::insert_tracking();
            }
        }
    }

    public static function reset() {
        self::$injected = false;
    }
}