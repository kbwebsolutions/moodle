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
 * Course completion progress report
 *
 * @package    report_booking
 * @copyright  2019 LMS Doctor, Inc.
 * @author     Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/lib/tablelib.php');
require 'viewclass_table.php';

global $OUTPUT, $PAGE, $USER;

require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/report/booking/index.php');
$PAGE->set_pagelayout('standard');
$table = new viewclass_table('uniqueid');

$download = optional_param('download', '', PARAM_ALPHA);
$table->is_downloading($download, 'booking_report', 'Sheet 1');

// Define headers
if (!$table->is_downloading()) {
    $PAGE->set_title(get_string('reportname','report_booking'));
    $PAGE->set_heading(get_string('reportname','report_booking'));
    echo $OUTPUT->header();
}

$fields = 'se.id \'sessionid\', co.fullname \'coursename\', bo.course,
            bo.name \'activity\',
            CONCAT(coach.firstname, " ", coach.lastname) \'coach\',
            CONCAT(participant.firstname, " ", participant.lastname) \'participant\',
            DATE_FORMAT(FROM_UNIXTIME(d.timestart), \'%M-%d-%Y\') \'date\',
            DATE_FORMAT(FROM_UNIXTIME(d.timestart), \'%H:%i\') \'starttime\',
            DATE_FORMAT(FROM_UNIXTIME(d.timefinish), \'%H:%i\') \'finishtime\',
            (SELECT da.data FROM {bookingform_session_data} da WHERE da.sessionid = se.id AND da.fieldid = (SELECT id FROM {bookingform_session_field} fi WHERE fi.shortname = \'Location\')) \'location\',
            (SELECT bfss.statuscode FROM {bookingform_signups_status} bfss WHERE bfss.signupid = bfs.id ORDER BY superceded ASC LIMIT 1) \'status\'';
$from = "{bookingform_sessions} se
            JOIN {bookingform_sessions_dates} d ON d.sessionid = se.id
            LEFT JOIN {bookingform_session_roles} bfsr ON se.id = bfsr.sessionid
            LEFT JOIN {user} coach ON bfsr.userid = coach.id
            LEFT JOIN {bookingform_signups} bfs ON se.id = bfs.sessionid
            LEFT JOIN {user} participant ON bfs.userid = participant.id
            JOIN {bookingform} bo ON bo.id = se.bookingform
            JOIN {course} co ON co.id = bo.course";
$sqlconditions = '1';
$sqlconditions .= ' ORDER BY co.sortorder ASC, bo.name ASC, se.datetimeknown DESC, d.timestart ASC';
$table->set_sql($fields, $from, $sqlconditions);

$table->define_baseurl("$CFG->wwwroot/report/booking/index.php");
$table->out(30, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
