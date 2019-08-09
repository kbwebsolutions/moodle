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
 * @subpackage learnerscript
 * @author: sreekanth
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

defined('MOODLE_INTERNAL') || die();
class report_assignment extends reportbase implements report {
    /**
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        parent::__construct($report);
        $this->parent = true;
        $this->columns = array('assignmentfield' => ['assignmentfield'],
                                'assignment' => array('gradepass', 'grademax', 'avggrade', 'submittedusers', 'completedusers', 'needgrading', 'totaltimespent', 'numviews'));
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $this->courselevel = false;
        $this->basicparams = array(['name' => 'coursecategories']);
        $this->filters = array('courses');
        $this->orderable = array('name', 'course', 'submittedusers', 'completedusers', 'needgrading', 'avggrade', 'numviews', 'totaltimespent', 'gradepass', 'grademax');
    }
    /**
     * [get_all_elements description]
     * @return [type] [description]
     */
    public function get_all_elements() {
        global $DB, $USER;
        $concatsql = '';
        $params = array();

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
            $fields = array('a.name', 'c.fullname', 'c.shortname', 'a.grade');
            $fields = implode(" LIKE '%$this->search%' OR ", $fields);
            $fields .= " LIKE '%$this->search%' ";
            $concatsql .= " AND ($fields) ";
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

        if (isset($this->params['filter_courses']) && $this->params['filter_courses'] > SITEID) {
            $courseid = $this->params['filter_courses'];
            $concatsql .= " AND c.id IN ($courseid)";
            $timespentsql = " AND mt.courseid IN ($courseid)";
        }
        if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $datefiltersql = " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $params['studentroleid'] = $studentroleid;
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $assigncountsql = "SELECT COUNT(DISTINCT a.id) ";
        $assignselectsql = "SELECT DISTINCT a.id, cm.id AS activityid, a.name AS name, c.fullname AS course, m.id AS module,csc.name AS section, cm.completion AS completion, c.id AS courseid, c.category AS categoryid";
        $assignsql = '';
        $coursesql  = (new querylib)->get_learners('', 'a.course');
        if (in_array('gradepass', $this->selectedcolumns)) {
            $assignsql .= ", ROUND(gi.gradepass, 0) AS gradepass";
        }
        if (in_array('grademax', $this->selectedcolumns)) {
            $assignsql .= ", a.grade AS grademax";
        }
        if (in_array('grademax', $this->selectedcolumns)) {
            $assignsql .= ", a.grade AS grademax";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $assignsql .= ", (SELECT SUM(mt.timespent) FROM {block_ls_modtimestats} mt WHERE mt.activityid = cm.id $timespentsql) AS totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $assignsql .= ", (SELECT COUNT(DISTINCT lsl.userid)  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id
                                = lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.userid > 2
                                AND lsl.contextlevel = 70 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND
                                u.confirmed = 1 AND u.deleted = 0) AS distinctusers,
                            (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid
                                WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND
                                lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND u.confirmed = 1
                                AND u.deleted = 0) AS numviews";
        }
        if (in_array('submittedusers', $this->selectedcolumns)) {
            $assignsql .= ", (SELECT count(asb.id) FROM {assign_submission} asb
                            WHERE asb.assignment = a.id AND asb.status = 'submitted'
                            AND asb.userid > 2 AND asb.userid IN ($coursesql)) AS submittedusers";
        }
        if (in_array('completedusers', $this->selectedcolumns)) {
            $assignsql .= ", (SELECT count(DISTINCT cmc.userid) FROM  {course_modules_completion} as cmc
                            JOIN {course_modules} as cmo ON cmo.id = cmc.coursemoduleid
                            JOIN {context} con ON con.instanceid = cmo.course
                            JOIN {role_assignments} ra ON ra.contextid = con.id
                            JOIN {role} r ON r.id =ra.roleid AND r.shortname = 'student'
                            WHERE ra.userid= cmc.userid AND cmc.completionstate > 0 AND cmo.course = c.id AND
                            cmo.module = 1 AND cmo.visible = 1 AND cmo.instance = a.id AND cmc.userid != 2 AND
                            cmc.userid IN ($coursesql)) AS completedusers";
        }
        if (in_array('avggrade', $this->selectedcolumns)) {
            $assignsql .= ", (SELECT ROUND(AVG(g.finalgrade), 2) FROM {grade_grades} g
                            WHERE g.finalgrade IS NOT NULL  AND g.itemid = gi.id AND g.userid IN ($coursesql))
                            AS avggrade";
        }
        if (in_array('needgrading', $this->selectedcolumns)) {
            $assignsql .= ", (SELECT count(asb.id) FROM {assign_submission} asb
                            WHERE asb.assignment = a.id AND asb.status = 'submitted'
                            AND asb.userid > 2 AND asb.userid IN (SELECT g.userid FROM {grade_grades} g
                            WHERE g.finalgrade IS NULL  AND g.itemid = gi.id AND g.userid IN ($coursesql)))
                            AS needgrading";
        }
        $assignsql .= " FROM {assign} a
                       JOIN {course_modules} as cm ON cm.instance = a.id
                       JOIN {modules} m ON cm.module = m.id
                       JOIN {course} c ON c.id = cm.course
                       LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemmodule = 'assign' AND
                                gi.iteminstance = a.id
                       JOIN {course_sections} as csc ON csc.id = cm.section
                       WHERE m.name = 'assign' AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1
                       $datefiltersql ";

        $assignsql .= $concatsql;

        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            if (!empty($this->role)) {
                $roleshortname = $this->role;
                $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
                if (!empty($mycourses)) {
                    $mycourseids = implode(',', array_keys($mycourses));
                    $assignsql .= " AND c.id IN ($mycourseids) ";
                } else {
                    return array(array(), 0);
                }
            } else {
                return array(array(), 0);
            }
        }
        try {
            $assigncount = $DB->count_records_sql($assigncountsql . $assignsql, $params);
        } catch (dml_exception $e) {
            $assigncount = 0;
        }
        try {
            if (!empty($this->sqlorder)) {
                $assignsql .= " ORDER BY " . $this->sqlorder;
            } else {
                $assignsql .= " ORDER BY a.id DESC";
            }
            $assignments = $DB->get_records_sql($assignselectsql . $assignsql , $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $assignments = array();
        }
        return array($assignments, $assigncount);
    }
    /**
     * [get_rows description]
     * @param  array  $users [description]
     * @return [type]        [description]
     */
    public function get_rows($quizs = array()) {
        return $quizs;
    }
}