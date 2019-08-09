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
 * LearnerScript
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Arun Kumar M
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
use block_learnerscript\local\querylib;
defined('MOODLE_INTERNAL') || die();
class report_courses extends reportbase implements report {

    public function __construct($report, $reportproperties) {
        global $DB;
        parent::__construct($report);
        $coursecolumns = $DB->get_columns('course');
        $usercolumns = $DB->get_columns('user');
        $columns = ['enrolments', 'completed', 'activities', 'progress', 'avggrade',
                    'enrolmethods', 'highgrade', 'lowgrade', 'badges', 'totaltimespent', 'numviews'];
        $this->columns = ['coursefield' => ['coursefield'] ,
                          'coursescolumns' => $columns];
        $this->conditions = ['courses' => array_keys($coursecolumns),
                             'user' => array_keys($usercolumns)];
        $this->components = array('columns', 'conditions', 'ordering', 'filters',
                                    'permissions', 'plot');
        $this->basicparams = array(['name' => 'coursecategories']);
        $this->filters = array('courses');
        $this->parent = true;
        $this->orderable = array('enrolments', 'completed', 'activities', 'progress', 'avggrade', 'enrolmethods',
                                'highgrade', 'lowgrade', 'badges', 'totaltimespent', 'numviews', 'fullname');

    }
    public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
        global $DB, $USER, $COURSE;
        $elements = array();
        $params = array();
        $concatsql = '';
        if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        }
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
        if (isset($this->search) && $this->search) {
            $fields = array('c.fullname', 'cat.name');
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $concatsql .= " AND ($fields) ";
        }
        if (!empty($this->params['filter_courses'])) {
            $courseids = $this->params['filter_courses'];
            $concatsql .= " AND c.id IN ($courseids) ";
        }
        if (!empty($this->params['filter_coursecategories'])) {
            $categoryid = $this->params['filter_coursecategories'];
            $ids = [$categoryid];
            $category = \coursecat::get($categoryid);
            $categoryids = array_merge($ids, $category->get_all_children_ids());
        }
        $catids = implode(',', $categoryids);
        if (!empty($catids)) {
             $concatsql .= " AND c.category IN ($catids) ";
        }
        if ($this->conditionsenabled) {
            $conditions = implode(',', $conditionfinalelements);
            if (empty($conditions)) {
                return array(array(), 0);
            }
            $concatsql .= " AND c.id IN ( $conditions )";
        }

        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $concatsql .= " AND c.timecreated BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $coursescountsql = "SELECT COUNT(DISTINCT c.id) ";
        $coursessql .= "SELECT c.id, c.fullname AS fullname, c.*, c.id AS courseid, c.category AS categoryid ";
        if (in_array('avggrade', $this->selectedcolumns)) { 
            $coursessql .= ", (SELECT ROUND(AVG(finalgrade),2) FROM {grade_grades} where itemid = gi.id) AS  avggrade";
        }
        if (in_array('highgrade', $this->selectedcolumns)) { 
            $coursessql .= ", ROUND(MAX(g.finalgrade),2) AS highgrade";
        }
        if (in_array('lowgrade', $this->selectedcolumns)) { 
            $coursessql .= ", ROUND(MIN(g.finalgrade),2) AS lowgrade";
        }
        if (in_array('completed', $this->selectedcolumns)) { 
            $coursessql .= ", COUNT(DISTINCT cc.userid) AS completed";
        }
        if (in_array('enrolments', $this->selectedcolumns)) { 
            $coursessql .= ", COUNT(DISTINCT ra.userid) AS enrolments";
        }
        if (in_array('progress', $this->selectedcolumns)) { 
            $coursessql .= ", ROUND(IF((COUNT(DISTINCT cc.userid)/COUNT(DISTINCT ue.userid) * 100) > 0, (COUNT(
                DISTINCT cc.userid)/COUNT(DISTINCT ue.userid) * 100), 0),2) AS progress";
        }
        if (in_array('activities', $this->selectedcolumns)) { 
            $coursessql .= ", (SELECT COUNT(id) FROM {course_modules} WHERE  course = c.id) AS activities";
        }
        if (in_array('enrolmethods', $this->selectedcolumns)) { 
            $coursessql .= ", (SELECT COUNT(id) FROM {enrol} WHERE status = 0 AND courseid = c.id) AS enrolmethods";
        }
        if (in_array('badges', $this->selectedcolumns)) { 
            $coursessql .= ", (SELECT COUNT(id) FROM {badge} WHERE status != 0  AND status != 2 AND courseid=c.id) AS badges";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) { 
            $coursessql .= ", (SELECT SUM(bt.timespent) from {block_ls_coursetimestats} as bt WHERE bt.courseid = c.id) as totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) { 
            $coursessql .= ", (SELECT COUNT(DISTINCT lsl.userid)  
                            FROM {logstore_standard_log} lsl JOIN {user} u ON
                            u.id = lsl.userid WHERE lsl.crud = 'r' AND lsl.contextlevel = 50  AND lsl.anonymous = 0
                            AND lsl.userid > 2 AND lsl.courseid = c.id AND u.confirmed = 1 AND u.deleted = 0 AND lsl.component != 'tool_usertours') AS distinctusers,
                            (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid
                            WHERE  lsl.crud = 'r' AND lsl.contextlevel = 50  AND lsl.anonymous = 0 AND lsl.userid > 2 AND
                                lsl.courseid = c.id AND u.confirmed = 1 AND u.deleted = 0 AND lsl.component != 'tool_usertours') AS numviews";
        }
        $sql = " FROM {course} c
                 LEFT JOIN {course_categories} cat ON cat.id = c.category
                 LEFT JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                 LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id AND ue.status = 0
                 LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = c.id AND cc.userid = ue.userid
                 LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
                 LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                JOIN {context} con ON c.id = con.instanceid AND con.contextlevel = 50
                JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.roleid = 5 AND ra.contextid = con.id 
                 LEFT JOIN {role} rl ON rl.id = ra.roleid AND rl.shortname = 'student'
                 LEFT JOIN {user} u ON u.id = ue.userid AND u.confirmed = 1 AND u.deleted = 0";
        $params['siteid'] = SITEID;
        $sql .= " WHERE c.visible = 1 AND c.id <> :siteid $concatsql ";
        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            if (!empty($this->role)) {
                $roleshortname = $this->role;
                $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
                if (!empty($mycourses)) {
                    $mycourseids = implode(',', array_keys($mycourses));
                    $sql .= " AND c.id IN ($mycourseids) ";
                } else {
                    return array(array(), 0);
                }
            } else {
                return array(array(), 0);
            }
        }
        try {
            $totalcourses = $DB->count_records_sql($coursescountsql . $sql, $params);
        } catch (dml_exception $e) {
            $totalcourses = 0;
        }
        $sql .= " GROUP BY c.id";
        if (!empty($this->sqlorder)) {
            $sql .= " ORDER BY ". $this->sqlorder;
        } else {
            if (!empty($sqlorder)) {
                $sql .= " ORDER BY c.$sqlorder ";
            } else {
                $sql .= " ORDER BY c.id DESC ";
            }
        }
        try {
            $courses = $DB->get_records_sql($coursessql . $sql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $courses = array();
        }
        return array($courses, $totalcourses);
    }
    public function get_rows($courses) {
        return $courses;
    }
}