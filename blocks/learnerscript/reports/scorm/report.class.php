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

class report_scorm extends reportbase implements report {
    /**
     * [__construct description]
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        $this->components = array('columns', 'filters', 'permissions', 'calcs', 'plot');
        $columns = ['highestgrade','avggrade','lowestgrade','noofcompletions','totaltimespent', 'numviews'];
        $this->columns = ['scormfield'=> ['scormfield'], 'scorm' => $columns];
        $this->courselevel = false;
        $this->basicparams = array(['name' => 'coursecategories']);
        $this->filters = array('courses');
        $this->parent = true;

    }
    /**
     * @return array array($activites, $totalactivites) list and count of Activities
     */
    public function get_all_elements() {
        global $DB, $COURSE;
        $params = array();
        $concatsql = " ";
        
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
            $fields = array('sc.name', 'c.fullname');
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $concatsql = " AND ($fields) ";
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
        if (!empty($this->params['filter_courses'])) {
            $courseids = $this->params['filter_courses'];
            $concatsql .= " AND c.id IN ($courseids) ";
            //$timespentsql = " AND mt.courseid IN ($courseids) ";
        }

        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $concatsql .= " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $coursesql  = (new querylib)->get_learners('','sc.course');
        $selectsql  = "SELECT sc.*, cm.id AS moduleid, m.id AS module, cm.id AS activityid, c.category AS categoryid, 
            (SELECT count(id) FROM {scorm_scoes_track} WHERE scormid=sc.id AND element = 'x.start.time' AND userid > 2 AND userid IN ($coursesql)) AS noofattempts ";
        if (in_array('highestgrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(MAX(finalgrade),2)
                            FROM {grade_grades} WHERE itemid = gi.id AND userid IN ($coursesql)) AS highestgrade";
        }
        if (in_array('avggrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(AVG(ggr.finalgrade),2) 
                            FROM {grade_grades}  ggr
                            JOIN {role_assignments} ras ON ras.userid = ggr.userid
                            JOIN {role} rl ON ras.roleid = rl.id
                            JOIn {context} ctx ON  ctx.contextlevel = 50
                            WHERE ggr.itemid=gi.id AND rl.shortname = 'student' AND ctx.instanceid = sc.course AND ctx.id = ras.contextid AND ras.userid IN ($coursesql)) AS avggrade";
        }
        if (in_array('lowestgrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(MIN(ggr.finalgrade),2) 
                            FROM {grade_grades}  ggr
                            JOIN {role_assignments} ras ON ras.userid = ggr.userid
                            JOIN {role} rl ON ras.roleid = rl.id
                            JOIn {context} ctx ON  ctx.contextlevel = 50
                            WHERE ggr.itemid=gi.id AND rl.shortname = 'student' AND ctx.instanceid = sc.course AND ctx.id = ras.contextid AND ras.userid IN ($coursesql)) AS lowestgrade";
        }
        if (in_array('noofcompletions', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT COUNT(DISTINCT cmc.id) FROM {course_modules_completion} cmc WHERE  cmc.coursemoduleid = cm.id AND cmc.userid > 2 AND cmc.completionstate > 0 AND cmc.userid IN ($coursesql)) as noofcompletions";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT SUM(mt.timespent) FROM {block_ls_modtimestats} as mt WHERE cm.id = mt.activityid) as totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT COUNT(DISTINCT lsl.userid)  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND u.confirmed = 1 AND u.deleted = 0) AS distinctusers,
                (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.courseid = cm.course AND lsl.userid > 2 AND lsl.anonymous = 0 AND u.confirmed = 1 AND u.deleted = 0) AS numviews";
        }
        $countsql   = "SELECT DISTINCT sc.id ";
        $sql        = " FROM {scorm} as sc
        				JOIN {course_modules} as cm ON cm.instance = sc.id
                        JOIN {modules} as m ON cm.module = m.id AND m.name='scorm'
                        JOIN {course} c ON c.id = cm.course
                        JOIN {grade_items} gi ON gi.iteminstance = cm.instance AND gi.itemtype = 'mod'
                         AND gi.itemmodule = 'scorm'
                        WHERE c.visible = 1 AND cm.visible = 1 AND cm.deletioninprogress = 0 $concatsql";

        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            if(!empty($this->role)){
                $roleshortname=$this->role;
                $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
                if (!empty($mycourses)) {
                    $mycourseids = implode(',', array_keys($mycourses));
                    $sql .= " AND c.id IN ($mycourseids) ";
                }else{
                    return array(array(), 0);
                }
            } else {
                return array(array(), 0);
            }
        }
        $sql .= " GROUP BY sc.id ";
        try{
           $totalactivite = $DB->get_records_sql($countsql . $sql, $params);
           $totalactivites = COUNT($totalactivite);
        } catch (dml_exception $e){
            $totalactivites = 0;
        }
        if(!empty($this->sqlorder)){
            $sql .=" ORDER BY ". $this->sqlorder;
        }else {
            $sql .= " ORDER BY sc.id DESC ";
        }
        try{
            $activites = $DB->get_records_sql($selectsql . $sql, $params, $this->start, $this->length);
        } catch (dml_exception $e){
            $activites = array();
        }
        return array($activites, $totalactivites);
    }
    /**
     * @param  array $activites Activites
     * @return array $reportarray Activities information
     */
    public function get_rows($data) {

        return $data;
    }
}
