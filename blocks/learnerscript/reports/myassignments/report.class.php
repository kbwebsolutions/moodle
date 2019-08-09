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
 * @author: sreekanth
 * @date: 2017
 */
require_once $CFG->libdir . '/completionlib.php';
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

class report_myassignments extends reportbase implements report {
	/**
	 * @param object $report Report object
	 * @param object $reportproperties Report properties object
	 */
	public function __construct($report, $reportproperties) {
		parent::__construct($report);
		$this->columns = array('assignmentfield' => ['assignmentfield'], 'myassignments' => array('gradepass', 'grademax', 'finalgrade', 'noofsubmissions','status', 'highestgrade', 'lowestgrade'));
		if (isset($this->role) && $this->role == 'student') {
			$this->parent = true;
		} else {
			$this->parent = false;
		}
		if ($this->role != 'student' || is_siteadmin()) {
			$this->basicparams = array(['name' => 'coursecategories'], ['name' => 'users']);
		} else {
			$this->basicparams = array(['name' => 'coursecategories']);
		}
		$this->courselevel = false;
		$this->components = array('columns', 'filters', 'permissions', 'plot');
		$this->filters = array('courses');
		$this->orderable = array('name','gradepass', 'grademax', 'finalgrade', 'noofsubmissions', 'highestgrade', 'lowestgrade','course');
	}
	/**
	 * [get_all_elements description]
	 * @return [type] [description]
	 */
	public function get_all_elements() {
		global $DB, $USER;
        
        if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        }
		if($this->role != 'student' && !isset($this->params['filter_users'])){
			$this->initial_basicparams('users');
            $this->params['filter_users'] = array_shift(array_keys($this->filterdata));
		}
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
		$userid = isset($this->params['filter_users']) && $this->params['filter_users'] > 0
					? $this->params['filter_users'] : $this->userid;
		$elements = array();
		$params = array();
		$this->courseid = isset($this->params['filter_courses']) ? $this->params['filter_courses'] : array();
		$concatsql = '';
		if (isset($this->search) && $this->search) {
			$fields = array('a.name', 'c.fullname','a.grade');
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
		if (!empty($this->courseid) && $this->courseid != '_qf__force_multiselect_submission') {
			$courseid = $this->courseid;
			$concatsql .= " AND cm.course =$courseid";
		}
		if (isset($this->params['filter_users']) && $this->params['filter_users'] > 0) {
          $filter_users = $this->params['filter_users'];
          $searchconcat .= " AND ra.userid IN ($filter_users)";
        }
 		if ($this->ls_startdate >= 0 && $this->ls_enddate) {
	        $concatsql .= " AND ra.timemodified BETWEEN :startdate AND :enddate ";
            $params['startdate'] = $this->ls_startdate;
            $params['enddate'] = $this->ls_enddate;
        }
        $params['userid'] = $userid;

		$mycourses = (new block_learnerscript\local\querylib)->get_rolecourses($userid, 'student', SITEID, '', '', '', false, false);
		if (empty($mycourses)) {
			return array(array(), 0);
		}
		$mycourseids = implode(',', array_keys($mycourses));
		$selectsql = "SELECT DISTINCT a.id, a.name as name, cm.course as courseid, c.fullname as course, m.id as module, m.name as type, cm.id AS activityid, c.category AS categoryid ";
        if (in_array('gradepass', $this->selectedcolumns)) {
            $selectsql .= ", ROUND(gi.gradepass, 2) as gradepass";
        }
        if (in_array('grademax', $this->selectedcolumns)) {
            $selectsql .= ", ROUND(a.grade, 2) as grademax";
        }
        if (in_array('finalgrade', $this->selectedcolumns)) {
            $selectsql .= ", ROUND(gg.finalgrade, 2) as finalgrade";
        }
        if (in_array('noofsubmissions', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT count(asb.id) FROM {assign_submission} asb
                            WHERE asb.assignment = a.id AND asb.status = 'submitted'
                            AND asb.userid = $userid) as noofsubmissions";
        }
        if (in_array('highestgrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(MAX(gg.finalgrade), 2) 
            			FROM {grade_grades} gg JOIN {grade_items} gi ON gi.id = gg.itemid
                        WHERE gi.itemmodule = 'assign' AND gi.iteminstance = a.id) AS highestgrade";
        }
        if (in_array('lowestgrade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT ROUND(MIN(gg.finalgrade), 2) 
            			FROM {grade_grades} gg JOIN {grade_items} gi ON gi.id = gg.itemid
                        WHERE gi.itemmodule = 'assign' AND gi.iteminstance = a.id) AS lowestgrade";
        }
		$countsql = "SELECT count(DISTINCT a.id) ";
        if(empty($this->params['filter_status']) || $this->params['filter_status'] == 'all') {
			$formsql = "  FROM {modules} as m
	                      JOIN {course_modules} as cm ON cm.module = m.id
	                      JOIN {assign} as a ON a.id = cm.instance
	                 LEFT JOIN {assign_submission} as asb ON asb.assignment = a.id AND asb.userid = :userid
	                      JOIN {course} as c ON c.id = cm.course
	                      JOIN {context} AS ctx ON c.id = ctx.instanceid
                          JOIN {role_assignments} as ra ON ctx.id = ra.contextid AND ra.userid = $userid
	                 LEFT JOIN {grade_items} gi ON gi.itemmodule = 'assign' AND gi.courseid = c.id AND gi.iteminstance = cm.instance
					 LEFT JOIN {grade_grades} gg ON gg.itemid =gi.id AND gg.userid = $userid
	                     WHERE c.visible = 1 AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.id IN ($mycourseids) AND m.name = 'assign'";
			$formsql .= " AND m.visible = 1 $concatsql $allassignfilter";
		} else if($this->params['filter_status'] == 'inprogress') {
			$formsql = "  FROM {modules} as m
	                      JOIN {course_modules} as cm ON cm.module = m.id
	                      JOIN {assign} as a ON a.id = cm.instance
	                      JOIN {assign_submission} as asb ON asb.assignment = a.id AND asb.userid = :userid
	                      JOIN {course} as c ON c.id = cm.course
	                      JOIN {context} AS ctx ON c.id = ctx.instanceid
                          JOIN {role_assignments} as ra ON ctx.id = ra.contextid AND ra.userid = $userid
	                 LEFT JOIN {grade_items} gi ON gi.itemmodule = 'assign' AND gi.courseid = c.id AND gi.iteminstance = cm.instance
					 LEFT JOIN {grade_grades} gg ON gg.itemid =gi.id AND gg.userid = $userid
	                     WHERE c.visible = 1 AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.id IN ($mycourseids) AND m.name = 'assign'";
			$formsql .= " AND m.visible = 1 $concatsql ";
		} else if($this->params['filter_status'] == 'completed') {
			$formsql = "  FROM {modules} as m
	                      JOIN {course_modules} as cm ON cm.module = m.id
	                      JOIN {assign} as a ON a.id = cm.instance
	                      JOIN {assign_submission} as asb ON asb.assignment = a.id AND asb.userid = :userid
	                      JOIN {course} as c ON c.id = cm.course
	                      JOIN {context} AS ctx ON c.id = ctx.instanceid
                          JOIN {role_assignments} as ra ON ctx.id = ra.contextid AND ra.userid = $userid
	                      JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = $userid
                     LEFT JOIN {grade_items} gi ON gi.itemmodule = 'assign' AND gi.courseid = c.id AND gi.iteminstance = cm.instance
					 LEFT JOIN {grade_grades} gg ON gg.itemid =gi.id AND gg.userid = $userid
	                     WHERE c.visible = 1 AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.id IN ($mycourseids) AND m.name = 'assign'";
			$formsql .= " AND m.visible = 1 $concatsql ";
		} else{
			$formsql = "  FROM {modules} as m
	                      JOIN {course_modules} as cm ON cm.module = m.id
	                      JOIN {assign} as a ON a.id = cm.instance
	                      JOIN {course} as c ON c.id = cm.course
	                      JOIN {context} AS ctx ON c.id = ctx.instanceid
                          JOIN {role_assignments} as ra ON ctx.id = ra.contextid AND ra.userid = $userid
	                 LEFT JOIN {grade_items} gi ON gi.itemmodule = 'assign' AND gi.courseid = c.id AND gi.iteminstance = cm.instance
					 LEFT JOIN {grade_grades} gg ON gg.itemid =gi.id AND gg.userid = $userid
	                     WHERE c.visible = 1 AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.id IN ($mycourseids) AND m.name = 'assign'
	                       AND a.id NOT IN (SELECT assignment FROM {assign_submission} WHERE userid = $userid)";
			$formsql .= " AND m.visible = 1 $concatsql $allassignfilter";
		}
		try {
			$assignmentcount = $DB->count_records_sql($countsql . $formsql, $params);
		} catch (dml_exception $e) {
			$assignmentcount = 0;
		}
		try {
			if(!empty($this->sqlorder)){
                $formsql .=" ORDER BY ". $this->sqlorder;
            } else{
				$formsql .= " ORDER BY a.id DESC ";
            }
			$assignments = $DB->get_records_sql($selectsql . $formsql, $params, $this->start, $this->length);
		} catch (dml_exception $e) {
			$assignments = array();
		}
		return array($assignments, $assignmentcount);
	}
	/**
	 * [get_rows description]
	 * @param  array  $assignments [description]
	 * @param  string $sqlorder    [description]
	 * @return [type]              [description]
	 */
	public function get_rows($assignments = array(), $sqlorder = '') {
		return $assignments;
	}
}