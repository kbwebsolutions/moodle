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
class report_gradedactivity extends reportbase implements report {
    /**
     * [__construct description]
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        $this->components = array('columns', 'filters', 'permissions', 'calcs', 'plot');
        $columns = ['modulename', 'highestgrade', 'averagegrade', 'lowestgrade', 'totaltimespent', 'numviews'];
        $this->columns = ['activityfield' => ['activityfield'], 'gradedactivity' => $columns];
        $this->courselevel = false;
        $this->basicparams = array(['name' => 'coursecategories']);  
        $this->filters = array('courses', 'modules', 'activities');
        $this->parent = true;
        $this->orderable = array('modulename', 'highestgrade', 'averagegrade', 'lowestgrade', 'totaltimespent', 'numviews', 'course');

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
        $modules = $DB->get_fieldset_select('modules', 'name', '', array('visible' => 1));
        foreach ($modules as $modulename) {
            $aliases[] = $modulename;
            $activities[] = "'$modulename'";
            $fields1[] = "COALESCE($modulename.intro, '')";
        }
        if (isset($this->search) && $this->search) {
            $fields = array('gi.itemname', 'c.fullname');
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
            $timespentsql = " AND mt.courseid IN ($courseids) ";
        }
        if (!empty($this->params['filter_modules'])) {
            $moduleid = $this->params['filter_modules'];
            $concatsql .= " AND m.id IN ($moduleid) ";
        }
        if (!empty($this->params['filter_activity'])) {
            $activityid = $this->params['filter_activity'];
            $concatsql .= " AND cm.id IN ($activityid) ";
        }
        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $concatsql .= " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $activitydescription = implode(',', $fields1);
        $coursesql  = (new querylib)->get_learners('', 'cm.course');
        $selectsql  = "SELECT cm.id AS activityid, m.id AS module, c.fullname
                        AS course, c.id AS courseid, c.category AS categoryid";
        if (in_array('modulename', $this->selectedcolumns)) {
            $selectsql .= ", gi.itemname AS modulename";
        }
        if (in_array('highestgrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(MAX(finalgrade),2) AS finalgrade 
                            FROM {grade_grades}  WHERE itemid = gi.id AND
                            userid IN ($coursesql)) AS highestgrade";
        }
        if (in_array('averagegrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(AVG(finalgrade),2) AS finalgrade 
                            FROM {grade_grades}  WHERE itemid=gi.id AND
                            userid IN ($coursesql)) AS averagegrade";
        }
        if (in_array('lowestgrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(MIN(finalgrade),2) AS finalgrade 
                            FROM {grade_grades}  WHERE itemid=gi.id AND
                            userid IN ($coursesql)) AS lowestgrade";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT SUM(mt.timespent)  FROM {block_ls_modtimestats} mt WHERE mt.activityid = cm.id $timespentsql) AS totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT COUNT(DISTINCT lsl.userid)  
                            FROM {logstore_standard_log} lsl JOIN {user} u ON u.id =
                            lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70
                            AND lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND u.confirmed = 1
                            AND u.deleted = 0) AS distinctusers,
                        (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE
                            lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.courseid
                            = cm.course AND lsl.userid > 2 AND lsl.anonymous = 0 AND u.confirmed = 1 AND u.deleted = 0)
                            AS numviews";
        }
        $countsql   = "SELECT count(cm.id) ";
        $sql        = " FROM {course_modules} cm
                        JOIN {modules} m ON cm.module = m.id
                        JOIN {grade_items} gi ON gi.iteminstance = cm.instance AND gi.itemtype = 'mod' AND
                                                gi.itemmodule = m.name
                        JOIN {course} c ON cm.course=c.id
                        JOIN {course_sections} csc ON csc.id = cm.section";

        foreach ($aliases as $alias) {
            $sql .= " LEFT JOIN {".$alias."} AS $alias ON $alias.id = cm.instance AND m.name = '$alias'";
        }
        $activitieslist = implode(',', $activities);

        $sql .= " WHERE c.visible = 1 AND cm.visible = 1 AND m.name IN ($activitieslist)";

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
        try {
            $totalactivites = $DB->count_records_sql($countsql . $sql, $params);
        } catch (dml_exception $e) {
            $totalactivites = 0;
        }
        if (!empty($this->sqlorder)) {
            $sql .= " ORDER BY ". $this->sqlorder;
        } else {
            $sql .= " ORDER BY cm.id DESC ";
        }
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