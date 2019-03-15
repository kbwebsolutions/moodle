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
 * Analytics
 *
 * This module provides extensive analytics on a platform of choice
 * Currently support Google Analytics and Piwik
 *
 * @package    local_analytics
 * @copyright  Bas Brands, Sonsbeekmedia 2017
 * @author     Bas Brands <bas@sonsbeekmedia.nl>, David Bezemer <info@davidbezemer.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use local_analytics\injector;

require_once(__DIR__.'/../../config.php');


/**
 * Used since Moodle 29.
 */
function local_analytics_extend_navigation() {
    injector::inject();
}

/**
 * Used since Moodle 29.
 */
function local_analytics_extend_settings_navigation() {
    injector::inject();
}

/**
 * Used in Moodle 30+ when a user is logged on.
 */
function local_analytics_extend_navigation_user_settings() {
    injector::inject();
}

/**
 * Used in Moodle 30+ on the frontpage.
 */
function local_analytics_extend_navigation_frontpage() {
    injector::inject();
}

/**
 * Used in Moodle 31+ when a user is logged on.
 */
function local_analytics_extend_navigation_user() {
    injector::inject();
}

/**
 * Proposed in MDL-53978.
 *
 * We are not using all callbacks provided there because the one below would cover all cases.
 * If approved, this would be the only needed callback, the others would provide legacy support.
 */
function tool_callbacktest_before_http_headers() {
    injector::inject();
}