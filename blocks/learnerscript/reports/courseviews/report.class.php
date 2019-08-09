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
 * @subpackage learnerscript
 * @author: sreekanth<sreekanth@eabyas.in>
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
defined('MOODLE_INTERNAL') || die();
class report_courseviews extends reportbase implements report {
    /**
     * [__construct description]
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        $this->components = array('columns', 'filters', 'permissions', 'calcs', 'plot');
        $this->columns = array('courseviews' => array('learner', 'views'));
        $this->courselevel = true;
        $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
        $this->parent = false;
        $this->orderable = array( );

    }
    /**
     * @return array array($activites, $totalactivites) list and count of Activities
     */
    public function get_all_elements() {
        global $DB, $COURSE;
        $params = array();
        $concatsql = " ";
        if (isset($this->search) && $this->search) {
            $fields = array("CONCAT(u.firstname, ' ' , u.lastname)");
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $concatsql = " AND ($fields) ";
        }
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
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
        $this->courseid = $this->params['filter_courses'];

        if (!empty($this->params['filter_courses']) && empty($this->params['filter_activity'])) {
            $concatsql .= " AND lsl.contextlevel = 50 ";
        }
        if (!empty($this->params['filter_courses'])) {
            $concatsql .= " AND lsl.courseid IN ($this->courseid) ";
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
        if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $datefiltersql = " AND lsl.timecreated BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $selectsql  = "SELECT COUNT(lsl.id) as views, lsl.userid as userid";
        if (in_array('learner', $this->selectedcolumns)) {
            $selectsql .= ", CONCAT(u.firstname, ' ', u.lastname) AS learner";
        }
        $countsql   = "SELECT COUNT(DISTINCT lsl.userid) ";
        $sql        = " FROM {logstore_standard_log} lsl
                        JOIN {course} c ON c.id = lsl.courseid
                        JOIN {user} AS u ON u.id = lsl.userid 
                       WHERE lsl.crud = 'r' AND lsl.userid > 2 AND u.confirmed = 1 
                        AND u.deleted = 0 $concatsql AND lsl.component != 'tool_usertours'
                        $datefiltersql";

        try {
            $totalactivites = $DB->count_records_sql($countsql . $sql, $params);
        } catch (dml_exception $e) {
            $totalactivites = 0;
        }

        $sql .= " GROUP BY lsl.userid ";
        try {
            $activites = $DB->get_recordset_sql($selectsql . $sql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $activites = array();
        }
        return array($activites, $totalactivites);
    }
    /**
     * @param  array $activites Activites
     * @return array $reportarray Activities information
     */
    public function get_rows($activites) {
        return $activites;
    }
}