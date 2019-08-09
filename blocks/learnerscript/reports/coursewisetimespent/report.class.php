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
 * @author: sreekanth
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
use block_learnerscript\local\querylib;
use block_learnerscript\local\ls;
defined('MOODLE_INTERNAL') || die();
class report_coursewisetimespent extends reportbase implements report {

    /**
     * [__construct description]
     * @param [type] $report           [description]
     * @param [type] $reportproperties [description]
     */
    public function __construct($report, $reportproperties = false) {
        global $DB;
        parent::__construct($report);
        $this->parent = true;
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $coursewisetimespentcolumns = array('totaltimespent');
        $this->columns = ['userfield' => ['userfield'],
                          'coursewisetimespentcolumns' => $coursewisetimespentcolumns];
        $this->basicparams = array(['name' => 'coursecategories']);    
        $this->filters = array('courses');
    }
    /**
     * [get_all_elements description]
     * @return [type] [description]
     */
    public function get_all_elements() {
        global $DB, $USER;
        $elements = array();
        $params = array();
        $concatsql = '';
        if (isset($this->search) && $this->search) {
            $fields = array('CONCAT(u.firstname, " ", u.lastname)', 'u.email');
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $concatsql .= " AND ($fields) ";
        }
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
        if (!empty($this->params['filter_courses'])) {
            $filtercourses = $this->params['filter_courses'];
            $concatsql .= " AND ct.courseid IN ($filtercourses)";
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
        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            $userrole = $this->role;
            if ($userrole == 'teacher' || $userrole == 'editingteacher' || $userrole == 'student') {
                $mycourses = (new querylib)->get_rolecourses($this->userid, $userrole, SITEID, '', '', false);
                $courseids = array();
                if (empty($mycourses)) {
                    return array(array(), 0);
                }
                foreach ($mycourses as $course) {
                    $courseids[] = $course->id;
                }
                $courseid = implode(',', $courseids);
                if (!empty($courseid)) {
                    $concatsql .= " AND ct.course IN ($courseid)";
                }
                if ($userrole == 'student') {
                    $concatsql .= " AND ct.userid = :userid ";
                    $params['userid'] = $this->userid;
                }
            } else {
                return array(array(), 0);
            }
        }
        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $concatsql .= " AND ct.timemodified BETWEEN :ls_startdate AND :ls_enddate ";
            $params['ls_startdate'] = $this->ls_startdate;
            $params['ls_enddate'] = $this->ls_enddate;
        }
        $uniquepopularresourcescount = "SELECT COUNT(DISTINCT ct.userid) ";
        $uniquepopularresources = "SELECT ct.userid , u.*, c.category AS categoryid ";
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $uniquepopularresources .= ", SUM(ct.timespent) AS totaltimespent";
        }
        $sql = " FROM {block_ls_coursetimestats} ct
                JOIN {user} u ON u.id = ct.userid
                LEFT JOIN {course} c ON c.id = ct.courseid
                WHERE 1=1 AND ct.userid > 2 AND u.confirmed = 1 AND u.deleted = 0 $concatsql ";
        try {
            $popularresourcescount = $DB->count_records_sql($uniquepopularresourcescount . $sql, $params);
        } catch (dml_exception $ex) {
            $popularresourcescount = 0;
        }
        $sql .= " GROUP BY ct.userid ";
        try {
            if (!empty($this->sqlorder)) {
                $sql .= " ORDER BY ". $this->sqlorder;
            } else {
                $sql .= " ORDER BY totaltimespent DESC";
            }
            $popularresources = $DB->get_records_sql($uniquepopularresources . $sql, $params, $this->start, $this->length);
        } catch (dml_exception $ex) {
            $popularresources = array();
        }
        return array($popularresources, (int)$popularresourcescount);
    }
    /**
     * [get_rows description]
     * @param  [type] $elements [description]
     * @return [type]           [description]
     */
    public function get_rows($elements) {
        global $CFG, $OUTPUT, $DB;
        $reportarray = array();
        if (!empty($elements)) {
            foreach ($elements as $record) {
                $returnobject = new stdClass();
                $record->totaltimespent = $record->totaltimespent ? (new ls)->strTime($record->totaltimespent) : '--';
                $record->fullname = fullname($record);
                $reportarray[] = $record;
            }
        }
        return $reportarray;
    }
}