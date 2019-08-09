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
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
use block_learnerscript\local\querylib;
class report_useractivities extends reportbase implements report {
    /**
     * [__construct description]
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        global $USER,$DB;
        parent::__construct($report);
        if($this->role != 'student'){
            $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses'],['name' => 'users']);
        } else{
            $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
        }
        $this->parent = false;
        $this->components = array('columns', 'filters', 'permissions', 'calcs', 'plot');
        $columns = ['modulename', 'moduletype', 'highestgrade', 'lowestgrade', 'finalgrade', 'firstaccess', 'lastaccess', 'totaltimespent', 'numviews', 'completedon', 'completionstatus'];
        $this->columns = ['activityfield'=> ['activityfield'], 'useractivitiescolumns' => $columns];
        $this->filters = array('modules','activities');
        $this->orderable = array('modulename', 'moduletype', 'highestgrade', 'lowestgrade', 'finalgrade', 'firstaccess', 'lastaccess', 'totaltimespent', 'numviews', 'completedon', 'completionstatus');
        $filiteruserlists = array();

        if(!isset($this->params['filter_users'])){
            $this->initial_basicparams('users');
            $this->params['filter_courses'] = $this->params['filter_courses'] > SITEID ? $this->params['filter_courses'] : SITEID;
            $coursecontext = context_course::instance($this->params['filter_courses']);
            $enrolledusers = array_keys(get_enrolled_users($coursecontext));
            $filiteruserlists = array();
            if(!empty($enrolledusers)) {
                $enrolledusers = implode(',', $enrolledusers);
                $filiteruserlists = $DB->get_records_sql_menu("SELECT id, concat(firstname,' ',lastname) as name FROM {user}
                                                   WHERE deleted = 0 AND confirmed = 1 AND id IN ($enrolledusers)");
            }
            if (is_siteadmin()) {
                $userfilter = array_keys($filiteruserlists);
                $this->params['filter_users'] = array_shift($userfilter);
            } else {
               $this->params['filter_users'] = $this->userid;
            }
        }
        if (!isset($this->params['filter_courses'])){
            $this->initial_basicparams('courses');
            if(is_siteadmin()){
                $userslist = array_keys($filiteruserlists);
                $this->params['filter_users'] = array_shift($userslist);
            }else{
                $this->params['filter_courses'] = $this->courseid;
          }
        }
    }
    /**
     * @return array array($activites, $totalactivites) list and count of Activities
     */
    public function get_all_elements() {
        global $DB, $COURSE;

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

        $moduleid = isset($this->params['filter_modules']) ? $this->params['filter_modules'] : 0;
        $status = isset($this->params['filter_status']) ? $this->params['filter_status'] : '';
        $activityid = isset($this->params['filter_activity']) ? $this->params['filter_activity'] : 0;
        $params = array();
        $concatsql = " ";
        $modules = $DB->get_fieldset_select('modules',  'name','', array('visible'=> 1));
        foreach ($modules as $modulename) {
            $aliases[] = $modulename;
            $activities[] = "'$modulename'";
            $fields1[] = "COALESCE($modulename.name,'')";
        }
        if (isset($this->search) && $this->search) {
            $fields2 = array('m.name',  'gi.itemname', 'c.fullname');
            $fields = $fields1 + $fields2;
            $fields = implode(" LIKE '%$this->search%' OR ", $fields );
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
        if (!empty($this->params['filter_courses'])) {
            $courseid = $this->params['filter_courses'];
            $concatsql .= " AND cm.course = $courseid";
        }
        if ($this->params['filter_users'] > 0) {
            $userid = $this->params['filter_users'];
            $concatsql .= " AND u.id = $userid";
        }
        if (isset($this->params['filter_modules']) && $this->params['filter_modules'] > 0) {
            $concatsql .= " AND cm.module = :moduleid";
            $params['moduleid'] = $this->params['filter_modules'];
        }
        if($this->ls_startdate >= 0 && $this->ls_enddate) {
            $concatsql .= " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if (isset($this->params['filter_activity']) && $this->params['filter_activity'] > 0) {
            $activitiesid = $this->params['filter_activity'];
            $concatsql .= " AND cm.id = $activitiesid";
            $params['activityid'] = $this->params['filter_activity'];
        }

        $activitynames = implode(',', $fields1);
        $selectsql  = "SELECT cm.id as activityid, m.id as module, cm.instance, cm.section, c.id as courseid, u.id as userid,  c.category AS categoryid ";

        if (in_array('modulename', $this->selectedcolumns)) {
            $selectsql .= ", CONCAT($activitynames) AS modulename";
        }
        if (in_array('moduletype', $this->selectedcolumns)) {
            $selectsql .= ", m.name AS moduletype";
        }
        if (in_array('highestgrade', $this->selectedcolumns)) {
            $selectsql .= ", ROUND(IF(MAX(gg.finalgrade), MAX(gg.finalgrade), 0), 2) AS highestgrade";
        }
        if (in_array('lowestgrade', $this->selectedcolumns)) {
            $selectsql .= ", ROUND(IF(MIN(gg.finalgrade), MIN(gg.finalgrade), 0), 2) AS lowestgrade";
        }
        if (in_array('finalgrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(gg.finalgrade, 2) FROM {grade_grades} gg WHERE gg.itemid = gi.id AND gg.userid = $userid)  AS finalgrade";
        }
        if (in_array('firstaccess', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT MIN(lsl.timecreated) FROM {logstore_standard_log} lsl WHERE lsl.contextinstanceid = cm.id AND lsl.userid = u.id ) AS firstaccess";
        }
        if (in_array('lastaccess', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT MAX(lsl.timecreated) FROM {logstore_standard_log} lsl WHERE lsl.contextinstanceid = cm.id AND lsl.userid = u.id ) AS lastaccess";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT SUM(timespent) FROM {block_ls_modtimestats} WHERE activityid=cm.id AND userid = u.id) AS totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT COUNT(DISTINCT lsl.userid)  FROM {logstore_standard_log} lsl WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.courseid = cm.course AND lsl.anonymous = 0) AS distinctusers,
                (SELECT COUNT('X')  FROM {logstore_standard_log} lsl WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND userid = u.id) AS numviews";
        }
        if (in_array('completedon', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT timemodified FROM {course_modules_completion}
                        WHERE completionstate <> 0 AND userid= $userid AND coursemoduleid = cm.id) as completedon";
        }
        if (in_array('completionstatus', $this->selectedcolumns)) {
            $selectsql .= ", cmc.completionstate as completionstatus";
        }

        $countsql   = "SELECT cm.id ";

        $sql        = " FROM {course_modules} cm
                        JOIN {modules} m ON cm.module = m.id
                        JOIN {course} c ON c.id = cm.course
                        JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                        JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {user} u ON u.id = ue.userid";
        foreach ($aliases as $alias) {
            $sql .= " LEFT JOIN {".$alias."} AS $alias ON $alias.id = cm.instance AND m.name = '$alias'";
        }
        $activitieslist = implode(',', $activities);
        $sql        .= "LEFT JOIN {grade_items} gi ON gi.iteminstance = cm.instance AND gi.itemtype = 'mod' AND gi.itemmodule = m.name
                        LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id
                        LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
                        WHERE u.deleted = 0 AND u.confirmed = 1 AND c.visible = 1 AND cm.visible = 1 AND
                        e.status = 0 AND u.deleted = 0 AND m.name IN ($activitieslist)";
        if($status == 'notcompleted'){
            $sql    .= " AND cm.id NOT IN (SELECT coursemoduleid FROM {course_modules_completion} WHERE completionstate <> 0 AND userid= $userid ) ";
        }
        if($status == 'completed'){
            $sql    .= " AND cm.id IN (SELECT coursemoduleid FROM {course_modules_completion}
                                              WHERE completionstate <> 0 AND userid= $userid ) ";
        }

        $sql .= " AND m.visible = 1 $concatsql ";

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
        $sql .= " GROUP BY cm.id ";
        try{
           $totalactivites = count($DB->get_records_sql($countsql . $sql, $params));
        } catch (dml_exception $e){
            $totalactivites = 0;
        }

        try{
            if(!empty($this->sqlorder)){
                $sql .=" ORDER BY ". $this->sqlorder;
            }else {
                $sql .= " ORDER BY cm.id DESC";
            }
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
    public function get_rows($activites) {
        return $activites;
    }
}
