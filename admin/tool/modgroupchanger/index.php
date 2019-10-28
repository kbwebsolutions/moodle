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
 * List groups from currente course and courses that user can manage groups.
 *
 * @package    tool_syncgroups
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require("{$CFG->dirroot}/admin/tool/modgroupchanger/lib.php");

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

$url = new moodle_url('/admin/tool/modgroupchanger/index.php', array('courseid'=>$courseid));

$PAGE->set_url($url);

require_login($course);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);
require_capability('moodle/course:managegroups', $context);

$pagetitle  = get_string('pluginname', 'tool_modgroupchanger');

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_modgroupchanger'));
echo html_writer::tag('p', get_string('intro', 'tool_modgroupchanger'));


$users =  get_users_for_moving($context);


$dbs = get_database_activites($courseid, 7);

var_dump($dbs);
echo $OUTPUT->footer();