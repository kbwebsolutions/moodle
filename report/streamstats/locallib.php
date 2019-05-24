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
 * @package    report
 * @subpackage streamstats
 * @copyright  2019 Chartered College of Teaching
 * @author     Kieran Briggs <kbriggs@chartered.college>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function get_workstream_categories() {
    global $DB;

    $sql = 'SELECT cc.name, cc.id from {course_categories} as cc WHERE cc.visible = 1';
    $rs = $DB->get_records_sql($sql);

    return $rs;
}

function get_workstream_options() {


    $categories = get_workstream_categories();
    foreach($categories as $cat) {
        $options[$cat->id] = $cat->name;
    }

    $mform = $this->_mform;

    $mform->add_element('select', 'categories', get_string('catoptions', 'report_streamstats'), $options);



    return $mform;

}

function forum_statistics($category = 1) {
    global $DB;

    $sql = "";
}