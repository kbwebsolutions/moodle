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
 * LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');
require_once("$CFG->dirroot/enrol/locallib.php");
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

class report_grades extends reportbase implements report {

    public function __construct($report, $reportproperties) {
        parent::__construct($report);
        $this->columns = array('userfield' => ['userfield'], 'gradecolumns' => array('grade', 'status'));
        $this->parent = false;
        $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses'], ['name' => 'activities']);
        $this->components = array('columns', 'conditions', 'ordering', 'filters',  'permissions', 'calcs', 'plot');
        $this->courselevel = true;
        $this->orderable = array('fullname', 'email');
    }

    public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
        global $DB, $USER;

        if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        }
        if (!isset($this->params['filter_courses']) && $this->params['filter_courses'] > SITEID) {
            $this->initial_basicparams('courses');
            $coursedata = array_keys($this->filterdata);
            $this->params['filter_courses'] = array_shift($coursedata);
        }
        if (!isset($this->params['filter_activity'])) {
            $this->initial_basicparams('activities');
            $userdata = array_keys($this->filterdata);
            $this->params['filter_activity'] = array_shift($userdata);
        }
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            $params = array();
            $list = array();
            foreach ($basicparams as $key => $basicparam) {
                $params[] = $basicparam;
                if (empty($this->params['filter_' . $basicparam]) && !in_array($basicparam, $params)) {
                    $list[$basicparam] = 1;
                }
            }
            if (!empty($list)) {
                return false;
            }
        }
        if (empty($this->params['filter_activity']) && !empty($this->params['filter_courses'])) {
            $modinfo = get_fast_modinfo($this->params['filter_courses']);
            if (!empty($modinfo->cms)) {
                foreach ($modinfo->cms as $k => $cm) {
                    $courseactivities[$k] = $cm->name;
                }
            }
            $activitiesdata = array_keys($courseactivities);
            $this->params['filter_activity'] = array_shift($activitiesdata);
        }
        if (!isset($this->params['filter_activity'])) {
            return array(array(), 0);
        }
        $this->courseid = $this->params['filter_courses'];
        $this->cmid = $this->params['filter_activity'];
        $elements = array();
        $params = array();
        $concatsql = '';
        if ($this->conditionsenabled) {
            $conditions = implode(',', $conditionfinalelements);
            if (empty($conditions)) {
                return array(array(), 0);
            }
            $concatsql = " AND u.id IN ( $conditions )";
        }

        if (isset($this->search) && $this->search) {
            $fields = array("CONCAT(u.firstname, ' ', u.lastname)", "u.email");
            $fields = implode(" LIKE :search1 OR ", $fields);
            $fields .= " LIKE :search2 ";
            $params['search1'] = '%' . $this->search . '%';
            $params['search2'] = '%' . $this->search . '%';
            $concatsql .= " AND ($fields) ";
        }

        if (!empty($this->params['filter_activity'])) {
            $activitysql = " AND cm.id IN ($this->cmid) ";
        }
        if (!empty($this->params['filter_coursecategories'])) {
            $categoryid = $this->params['filter_coursecategories'];
            $ids = [$categoryid];
            $category = \coursecat::get($categoryid);
            $categoryids = array_merge($ids, $category->get_all_children_ids());
        }
        $catids = implode(',', $categoryids);
        if (!empty($catids)) {
             $concatsql .= " AND c1.category IN ($catids) ";
        }
        if (!empty($this->params['filter_courses'])) {
            $courseid = $this->params['filter_courses'];
            $concatsql .= " AND c1.id = $courseid ";
        }

        if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $concatsql .= " AND u.timecreated BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }

        if ($this->conditionsenabled) {
            $conditions = implode(',', $conditionfinalelements);
            if (empty($conditions)) {
                return array(array(), 0);
            }
            $concatsql .= " AND u.id IN ($conditions)";
        }
        $params['deleted'] = 0;
        $params['suspended'] = 0;
        $params['confirmed'] = 1;
        $params['contextlevel'] = CONTEXT_COURSE;
        $params['ej1_active'] = ENROL_USER_ACTIVE;
        $params['ej1_enabled'] = ENROL_INSTANCE_ENABLED;
        $params['ej1_now1'] = round(time(), -2); // Improves db caching.
        $params['ej1_now2'] = $params['ej1_now1'];
        $params['ej1_courseid'] = $this->courseid;
        $params['courseid'] = $this->courseid;
        $params['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'student'));
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $totaluserssql = "SELECT COUNT(u.id) ";
        $userssql = "SELECT u.id as userid, CONCAT(u.firstname, u.lastname) as fullname, c1.category AS categoryid, u.* ";
        $sql = " FROM {user} u
                 JOIN {user_enrolments} ue1 ON ue1.userid = u.id
                 JOIN {enrol} e1 ON e1.id = ue1.enrolid
                 JOIN {course} c1 ON c1.id = e1.courseid
                 WHERE 1 = 1 AND u.id IN (SELECT u.id
                    FROM {course} AS c
                    JOIN {enrol} AS e ON c.id = e.courseid AND e.status = 0
                    JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.status = 0
                    JOIN {role_assignments} AS ra ON ra.userid = ue.userid
                    JOIN {context} con ON c.id = con.instanceid
                    JOIN {role} AS rl ON rl.id = ra.roleid AND rl.shortname = 'student'
                    JOIN {course_modules} AS cm ON cm.course = c.id
                    JOIN {user} AS u ON u.id = ue.userid
                    WHERE c.visible = 1 AND ra.roleid = :roleid AND ra.contextid = con.id
                    AND u.confirmed = 1 AND u.deleted = 0 AND cm.visible = 1 $activitysql AND c.id = :ej1_courseid)
                 AND u.deleted = 0 AND u.confirmed = 1 $concatsql";

        try {
            $totalusers = $DB->count_records_sql($totaluserssql . $sql, $params);
        } catch (dml_exception $e) {
            $totalusers = 0;
        }
        try {
            if (!empty($this->sqlorder)) {
                $sql .= " ORDER BY ". $this->sqlorder;
            } else {
                $sql .= " ORDER BY fullname DESC";
            }
            $users = $DB->get_records_sql($userssql . $sql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $users = array();
        }

        return array($users, $totalusers);
    }

    public function get_rows($users) {
        global $DB, $CFG, $OUTPUT;
        $this->courseid = isset($this->params['filter_courses']) ? $this->params['filter_courses'] : SITEID;
        $this->cmid = isset($this->params['filter_activity']) ? $this->params['filter_activity'] : null;
        if (!$this->cmid) {
            return array();
        }
        $course = $DB->get_record('course', array('id' => $this->courseid));
        $completioninfo = new completion_info($course);
        $params['cmid'] = $this->cmid;
        $params['itemtype'] = 'mod';
        $sql = " SELECT gi.*
                   FROM {grade_items} gi
                   JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                   JOIN {modules} m ON m.id = cm.module
                  WHERE cm.id = :cmid AND gi.itemtype = :itemtype
                        AND m.name = gi.itemmodule AND gi.courseid = cm.course";
        $gradeitem = $DB->get_record_sql($sql, $params);
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/lib.php');
        require_once($CFG->dirroot . '/grade/report/grader/lib.php');
        $data = array();
        if (!empty($users)) {
            foreach ($users as $user) {
                $gradedata = grade_grade::fetch_users_grades($gradeitem, array($user->id));
                if (!empty($gradedata)) {
                    $user->grade = $gradedata[$user->id]->finalgrade ? round($gradedata[$user->id]->finalgrade, 2) : '-';
                } else {
                    $user->grade = '-';
                }
                $cm = new stdClass();
                $cm->id = $this->cmid;
                $completion = $completioninfo->get_data($cm, false, $user->id);
                switch($completion->completionstate) {
                    case COMPLETION_INCOMPLETE :
                            $completionstatus = 'Not Completed';
                    break;
                    case COMPLETION_COMPLETE :
                            $completionstatus = 'Completed';
                    break;
                    case COMPLETION_COMPLETE_PASS :
                            $completionstatus = 'Completed (achieved pass grade)';
                    break;
                    case COMPLETION_COMPLETE_FAIL :
                            $completionstatus = 'Fail';
                    break;
                }
                $user->status = $completionstatus;
                $data[] = $user;
            }
        }
        return $data;
    }
}