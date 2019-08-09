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

class report_usersresources extends reportbase implements report {

	/**
	 * [__construct description]
	 * @param [type] $report           [description]
	 * @param [type] $reportproperties [description]
	 */
	public function __construct($report, $reportproperties = false) {
		parent::__construct($report);
		$this->parent = false;
		$this->components = array('columns', 'filters', 'permissions', 'plot');
		$resourcescolumns = array('totalresources','totaltimespent','numviews');
		$this->columns = ['userfield' => ['userfield'] ,'usersresources' => $resourcescolumns];
		$this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
		$this->filters = ['users'];
		$this->courselevel = true;
		$this->orderable = array('fullname', 'totalresources', 'totaltimespent', 'numviews');
	}
	/**
	 * [get_all_elements description]
	 * @return [type] [description]
	 */
	function get_all_elements() {
		global $DB, $USER;
        
        if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        }
		if (!isset($this->params['filter_courses']) && $this->params['filter_courses'] > SITEID) {
            $this->initial_basicparams('courses');
            $this->params['filter_courses'] = array_shift(array_keys($this->filterdata));
        }
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
		$params = array();
		$concatsql = '';
		$modules = $DB->get_fieldset_select('modules',  'name', '');
        foreach ($modules as $modulename) {
        	$resourcearchetype = plugin_supports('mod', $modulename, FEATURE_MOD_ARCHETYPE);
        	if($resourcearchetype){
        		$resources[] = "'$modulename'";
        	}
        }
		if (isset($this->search) && $this->search) {
          $fields = array("CONCAT(u.firstname, ' ', u.lastname)", "u.email");
          $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
          $fields .= " LIKE '%" . $this->search . "%' ";
          $concatsql .= " AND ($fields) ";
        }

		 if ($this->params['filter_courses'] > SITEID) {
            $courseid = $this->params['filter_courses'];
            $concatsql .= " AND c.id = $courseid";
			$filtersql = " AND c.id = $courseid";
			$timespentsql = " AND mt.courseid = $courseid";
			$numviewssql = " AND lsl.courseid = $courseid";
        }
		if (isset($this->params['filter_users']) && $this->params['filter_users'] > SITEID) {
			$filter_users = $this->params['filter_users'];
			$concatsql .= " AND u.id = $filter_users";
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
		if ($this->ls_startdate >= 0 && $this->ls_enddate) {
			$concatsql .= " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
			$params['ls_startdate'] = $this->ls_startdate;
			$params['ls_enddate'] = $this->ls_enddate;
		}
        if ($this->role == 'student') {
            return array(array(), 0);
        }
		$imploderesources = implode(', ', $resources);
		$studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
		$countsql   = "SELECT count(DISTINCT u.id) ";
		$selectsql   = "SELECT DISTINCT u.id as userid, u.email, CONCAT(u.firstname,' ', u.lastname) AS fullname, c.category AS categoryid ";
		if (in_array('totalresources', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT count(cm.id)
	                       FROM {course_modules} as cm
	                       JOIN {modules} as m ON m.id = cm.module
	                      WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND m.name IN ($imploderesources)
	                        AND cm.course IN ( SELECT  c.id FROM {course} c
												JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
												JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
												JOIN {role_assignments} ra ON ra.userid = ue.userid
												JOIN {role} r ON r.id =ra.roleid AND r.shortname = 'student'
											   WHERE ra.userid= u.id AND c.visible = 1 $filtersql)  ) AS totalresources";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT SUM(mt.timespent) from {block_ls_modtimestats} as mt JOIN {course_modules} cm ON cm.id = mt.activityid JOIN {modules} m ON m.id = cm.module WHERE m.name IN ($imploderesources) AND mt.userid = u.id $timespentsql) as totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT COUNT(DISTINCT lsl.userid) 
            		FROM {logstore_standard_log} lsl JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid JOIN {modules} m ON m.id = cm.module WHERE m.name IN ($imploderesources) AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.anonymous = 0 AND lsl.userid = u.id) AS distinctusers, 
            	(SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid JOIN {modules} m ON m.id = cm.module WHERE m.name IN ($imploderesources) AND lsl.crud = 'r' AND lsl.contextlevel = 70  AND lsl.anonymous = 0 AND lsl.userid = u.id $numviewssql) AS numviews";
        }
		$fromsql  = "  FROM {course} AS c
	                      JOIN {enrol} AS e ON c.id = e.courseid AND e.status = 0
	                      JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.status = 0
	                      JOIN {role_assignments} AS ra ON ra.userid = ue.userid
	                      JOIN {context} con ON c.id = con.instanceid
                      	  JOIN {role} AS rl ON rl.id = ra.roleid AND rl.shortname = 'student'
	                      JOIN {user} AS u ON u.id = ue.userid
	                      WHERE c.visible = 1 AND ra.roleid = :roleid AND ra.contextid =con.id
	                      AND u.confirmed = 1 AND u.deleted = 0 $concatsql";

		if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            $roleshortname = $this->role;
            $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
            $mycourseids = implode(',', array_keys($mycourses));
            if (!empty($mycourses)) {
                $mycourseids = implode(',', array_keys($mycourses));
                $sql .= " AND c.id IN ($mycourseids) ";
            } else {
                return array(array(), 0);
            }
        }
        $params['roleid'] = $studentroleid;
        try {
			$resourcescount = $DB->count_records_sql($countsql . $fromsql , $params);
		} catch (dml_exception $ex) {
			$resourcescount = 0;
		}
		try {
			if(!empty($this->sqlorder)){
	            $fromsql .=" ORDER BY ". $this->sqlorder;
	        }else {
				$fromsql .= " ORDER BY u.id DESC ";
	        }
			$resources = $DB->get_records_sql($selectsql . $fromsql, $params,$this->start, $this->length);
		} catch (dml_exception $ex) {
			$resources = array();
		}
		return array($resources, (int)$resourcescount);
	}
	/**
	 * [get_rows description]
	 * @param  [type] $elements [description]
	 * @return [type]           [description]
	 */
	function get_rows($elements) {
		global $CFG, $OUTPUT, $DB;

		return $elements;
	}
}