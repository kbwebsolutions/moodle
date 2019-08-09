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

/** LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
use block_learnerscript\local\pluginbase;
class plugin_usersincoursereport extends pluginbase {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('usersincoursereport', 'block_learnerscript');
        $this->reporttypes = array('courseactivities', 'grades', 'useractivities', 'usercourses', 'topic_wise_performance', 'courseprofile', 'courseviews', 'noofviews', 'userassignments', 'userquizzes', 'usersscorm', 'usersresources');
    }

    function summary($data) {
        return get_string('usersincoursereport_summary', 'block_learnerscript');
    }

    function execute($userid, $context, $data) {
        global $DB, $CFG;

        if($context->contextlevel != 50) {
            return false;
        }
        return is_enrolled($context, $userid);
    }
}