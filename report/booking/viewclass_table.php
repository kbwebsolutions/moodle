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
 * Table class.
 *
 * @package    report_booking
 * @copyright  2019 LMS Doctor, Inc.
 * @author     Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Table class to be put in managecourses.php selfstudy manage course page.
 * for defining some custom column names and proccessing
 */
class viewclass_table extends table_sql {

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *                      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array('coursename', 'activity', 'coach', 'participant', 'date', 'starttime', 'finishtime', 'location', 'status');
        $this->collapsible(false);
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array();
        $headers[] = get_string('coursename', 'report_booking');
        $headers[] = get_string('activity', 'report_booking');
        $headers[] = get_string('coach', 'report_booking');
        $headers[] = get_string('participant', 'report_booking');
        $headers[] = get_string('date', 'report_booking');
        $headers[] = get_string('starttime', 'report_booking');
        $headers[] = get_string('finishtime', 'report_booking');
        $headers[] = get_string('location', 'report_booking');
        $headers[] = get_string('status', 'report_booking');
        $this->define_headers($headers);
    }

    /**
     * This function is called for each data row to allow processing of
     * columns which do not have a *_cols function.
     * @return string return processed value. Return NULL if no change has
     *                       been made.
     */
    function other_cols($colname, $value) {
        // For security reasons we don't want to show the password hash.
    }

    /**
     * This function is called for each data row to allow processing of the
     * coursname value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return coursename with link to course or coursename only
     *     when downloading.
     */
    function col_coursename($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->coursename;
        } else {
            $courseurl = new moodle_url('/course/view.php', array('id' => $values->course));
            return html_writer::link($courseurl, $values->coursename);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * status value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return status string.
     */
    function col_status($values) {
        global $CFG;

        require_once($CFG->dirroot . "/mod/bookingform/lib.php");

        if (!empty($values->status)) {
            if ($identifier = bookingform_get_status($values->status)) {
                return get_string('status_' . $identifier, 'mod_bookingform');
            }
        }

        return '';
    }

}
