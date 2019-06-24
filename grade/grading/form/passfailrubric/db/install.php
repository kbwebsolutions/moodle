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
 *
 * Grading method controller for the btec plugin
 *
 * @package    gradingform_passfailrubric
 * @copyright  2019 Titus Learning by  Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Grading method controller for the btec plugin
 *
 * @package    gradingform_passfailrubric
 * @copyright  2019 Titus Learning by  Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
function xmldb_gradingform_passfailrubric_install() {
    global $DB;
    if (!$DB->record_exists('scale', ['name' => 'refer_fail_pass'])) {
        $record = new stdClass();
        $record->courseid = 0;
        $record->userid = 0;
        $record->name = 'refer_fail_pass';
        $record->scale = 'Refer,Fail,Pass';
        $record->description = get_string('scale_description', 'gradingform_passfailrubric');
        $record->descriptionformat = 1;
        $DB->insert_record('scale', $record);
    }
}
