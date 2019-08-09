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

/** LearnerScript
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
use block_learnerscript\local\ls;
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\querylib;

class plugin_roleincourse extends pluginbase {

    public function init() {
        $this->form = true;
        $this->unique = false;
        $this->fullname = get_string('roleincourse', 'block_learnerscript');
        $this->reporttypes = array('courses', 'sql', 'users', 'statistics', 'timeline', 'categories',
            'activitystatus', 'listofactivities', 'coursesoverview', 'usercourses', 'grades',
            'scorm_activities_course', 'competencycompletion', 'myassignments', 'useractivities',
            'assignments', 'userassignments', 'resources', 'myscorm', 'quizzes', 'userquizzes',
            'assignment', 'myquizs', 'topic_wise_performance', 'courseaverage', 'uniquelogins',
            'popularresources','userbadges', 'scorm', 'usersresources', 'usersscorm', 'badges',
            'pageresourcetimespent', 'coursewisetimespent', 'gradedactivity',
            'resources_accessed', 'timespent');
    }

    public function summary($data) {
        global $DB;
        $rolename = $DB->get_field('role', 'shortname', array('id' => $data->roleid));
        $coursename = $DB->get_field('course', 'fullname', array('id' => $this->report->courseid));
        return $rolename . ' ' . $coursename;
    }

    public function execute($userid, $context, $data) {
        global $CFG, $DB;
        $roles = get_user_roles($context, $userid);
        if (!empty($roles)) {
            foreach ($roles as $rol) {
                if ($rol->roleid == $data->roleid)
                    return true;
            }
        }
        if ($context->contextlevel == 10) {

            $components = (new ls)->cr_unserialize($this->report->components);
            $permissions = (isset($components['permissions'])) ? $components['permissions'] : array();

            if (!empty($this->role)) {
                // $checkrole = (new querylib)->get_rolecourses($userid, $this->role);
                // if (!empty($checkrole)) {
                //     return true;
                // }

                $rolepermissions = array();
                foreach ($permissions['elements'] as $p) {
                    if ($p['pluginname'] == 'roleincourse') {
                        $rolepermissions[] = $p['formdata']->roleid;
                    }
                }
                sort($rolepermissions);
                $rolelist = (new ls)->get_currentuser_roles($userid);
                $roleslistids = array_keys($rolelist);
                $currentroleid = $DB->get_field('role', 'id', array('shortname' => $this->role));
                if (in_array($currentroleid, $rolepermissions)) {
                    return true;
                }
            } else {
                $datarole = $DB->get_field('role', 'shortname', array('id' => $data->roleid));
                $checkrole = in_array($datarole, $this->userroles);
                return $checkrole;
            }
        }
        return false;
    }
}