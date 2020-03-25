<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    filter
 * @subpackage urlresource
 * @copyright  2014 Andreas Wagner, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '../../../config.php');

$url = required_param('url', PARAM_RAW);
$courseid = required_param('courseid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_course_login($course);

// ...start setting up the page.
$context = context_course::instance($course->id, MUST_EXIST);
require_capability('filter/urlresource:loadnewimage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/filter/urlresource/pages/urlreplace.php', array('courseid' => $courseid)));

require_once($CFG->dirroot . '/filter/urlresource/lib.php');

$url = filter_url_resource_helper::clean_url($url);
switch ($action) {

    case 'loadnewimages' :

        $urlresourcehelper = filter_url_resource_helper::instance($course->id);
        $result = $urlresourcehelper->load_loadnewimages($url);
        echo json_encode($result);
        die;

        break;

    default:
         print_error('unknown action: ' . $action);

}