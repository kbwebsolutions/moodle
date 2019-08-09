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
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
defined('MOODLE_INTERNAL') || die();
class report_courseactivities extends reportbase implements report {
    /**
     * [__construct description]
     * @param [type] $report           [description]
     * @param [type] $reportproperties [description]
     */
    public function __construct($report, $reportproperties) {
        parent::__construct($report);
        $this->columns = array('activityfield' => ['activityfield'], 'courseactivitiescolumns' => array('activityname', 'learnerscompleted', 'grademax', 'gradepass', 
                        'averagegrade', 'highestgrade', 'lowestgrade', 'progress', 
                        'totaltimespent', 'numviews', 'grades'));
        $this->parent = false;
        $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
        $this->components = array('columns', 'filters', 'permissions', 'calcs', 'plot');
        $this->courselevel = true;
        $this->orderable = array('activityname', 'learnerscompleted', 'grademax', 'gradepass', 'averagegrade', 'highestgrade', 'lowestgrade', 'progress', 'totaltimespent',
                                    'numviews');
    }
    /**
     * [get_all_elements description]
     * @return [type] [description]
     */
    public function get_all_elements() {
        global $DB, $USER, $COURSE;
        $params = array();
        $concatsql = '';
         if (!isset($this->params['filter_courses']) && $this->params['filter_courses'] > SITEID) {
            $this->initial_basicparams('courses');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_courses'] = array_shift($filterdata);
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
        $modules = $DB->get_fieldset_select('modules', 'name', '', array('visible' => 1));
        foreach ($modules as $modulename) {
            $aliases[] = $modulename;
            $activities[] = "'$modulename'";
            $fields1[] = "COALESCE($modulename.name,'')";
        }
        if (isset($this->search) && $this->search) {
            $fields2 = array('m.name', 'gi.grademax', 'gi.gradepass');
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
        if ($this->params['filter_courses'] > SITEID) {
             $courseid = $this->params['filter_courses'];
             $concatsql .= " AND cm.course = $courseid";
             $timespentsql = " AND mt.courseid = $courseid";
        }
        if (isset($this->params['filter_modules']) && $this->params['filter_modules'] > 0) {
            $concatsql .= " AND cm.module = :moduleid";
            $params['moduleid'] = $this->params['filter_modules'];
        }
        if (isset($this->params['filter_activity']) && $this->params['filter_activity'] > 0) {
            $concatsql .= " AND cm.id = :activityid";
            $params['activityid'] = $this->params['filter_activity'];
        }
        if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $concatsql .= " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }

        $activitynames = implode(',', $fields1);
        $coursesql  = (new querylib)->get_learners('', 'cm.course');
        $totalactivitiessql = "SELECT COUNT(cm.id) ";
        $activitiessql = " SELECT cm.id AS activityid, m.id AS moduleid, cm.instance, 
                                cm.course AS courseid, gi.itemname AS modulename, c.category AS categoryid";
                if (in_array('activityname', $this->selectedcolumns)) {
                    $activitiessql .= ", CONCAT($activitynames) AS activityname";
                }
                if (in_array('learnerscompleted', $this->selectedcolumns)) {
                    $activitiessql .= ", (SELECT COUNT(id) FROM {course_modules_completion}  WHERE coursemoduleid = cm.id
                        AND completionstate > 0 AND userid > 2 AND userid IN ($coursesql))
                        AS learnerscompleted";
                }
                if (in_array('grademax', $this->selectedcolumns)) {
                    $activitiessql .= ", ROUND(gi.grademax, 2) AS grademax";
                }
                if (in_array('gradepass', $this->selectedcolumns)) {
                    $activitiessql .= ", ROUND(gi.gradepass, 2) AS gradepass";
                }
                if (in_array('averagegrade', $this->selectedcolumns)) {
                    $activitiessql .= ", (SELECT ROUND(AVG(gg.finalgrade)) 
                            FROM {grade_grades} gg WHERE gg.itemid = gi.id
                            AND gg.userid IN ($coursesql)) AS averagegrade";
                }
                if (in_array('highestgrade', $this->selectedcolumns)) {
                    $activitiessql .= ", (SELECT ROUND(MAX(finalgrade),2) AS finalgrade 
                            FROM {grade_grades}  WHERE itemid = gi.id
                            AND userid IN ($coursesql)) AS highestgrade";
                }
                if (in_array('lowestgrade', $this->selectedcolumns)) {
                    $activitiessql .= ", (SELECT ROUND(MIN(finalgrade),2) AS finalgrade 
                            FROM {grade_grades}  WHERE itemid=gi.id
                            AND userid IN ($coursesql)) AS lowestgrade";
                }
                if (in_array('progress', $this->selectedcolumns)) {
                    $activitiessql .= ", ROUND(((SELECT COUNT(id) 
                                    FROM {course_modules_completion}
                                    WHERE coursemoduleid = cm.id AND completionstate > 0 AND userid IN ($coursesql))/(
                                        SELECT count(DISTINCT u.id) FROM {user} u
                                    JOIN {role_assignments} ra ON ra.userid = u.id
                                    JOIN {context} ctx ON ctx.id = ra.contextid
                                    JOIN {course} c ON c.id = ctx.instanceid
                                    JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                                    WHERE c.id = cm.course AND ra.userid IN ($coursesql)))* 100, 2) AS progress";
                }
                if (in_array('totaltimespent', $this->selectedcolumns)) {
                    $activitiessql .= ", (SELECT SUM(mt.timespent)  
                            FROM {block_ls_modtimestats} mt WHERE mt.activityid = cm.id 
                            $timespentsql) AS totaltimespent";
                }
                if (in_array('numviews', $this->selectedcolumns)) {
                    $activitiessql .= ", (SELECT COUNT(DISTINCT lsl.userid)  
                            FROM {logstore_standard_log} lsl JOIN {user} u
                                ON u.id = lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND
                                lsl.contextlevel = 70 AND lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0
                                AND u.confirmed = 1 AND u.deleted = 0) AS distinctusers,
                            (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid
                                WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70
                                AND lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND u.confirmed = 1
                                AND u.deleted = 0) AS numviews";
                }
                if (in_array('grades', $this->selectedcolumns)) {
                    $activitiessql .= ", 'Grades'";
                }

        $sql = " FROM {modules} m
                 JOIN {course_modules} cm ON cm.module = m.id
		 JOIN {course} c ON c.id = cm.course";
        foreach ($aliases as $alias) {
            $sql .= " LEFT JOIN {".$alias."} AS $alias ON $alias.id = cm.instance AND m.name = '$alias'";
        }
        $activitieslist = implode(',', $activities);
        $sql .= " LEFT JOIN {grade_items} gi ON gi.itemmodule = m.name
                       AND gi.courseid = cm.course AND gi.iteminstance = cm.instance ";
        $sql .= " WHERE m.visible = :mvisible AND m.name IN ($activitieslist) $concatsql ";
        $params['siteid'] = SITEID;
        $params['cmvisible'] = 1;
        $params['mvisible'] = 1;
        try {
            $totalactivities = $DB->count_records_sql($totalactivitiessql . $sql, $params);
        } catch (dml_exception $e) {
            $totalactivities = 0;
        }
        try {
            if (!empty($this->sqlorder)) {
                $sql .= " ORDER BY " . $this->sqlorder;
            } else {
                $sql .= " ORDER BY cm.id DESC ";
            }
            $activities = $DB->get_recordset_sql($activitiessql . $sql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $activities = array();
        }

        return array($activities, $totalactivities);
    }
    /**
     * [get_rows description]
     * @param  array  $activites [description]
     * @return [type]            [description]
     */
    public function get_rows($activites = array()) {
        return $activites;
    }
}