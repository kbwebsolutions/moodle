<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License AS published by
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
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

class report_userassignments extends reportbase implements report {
	/**
	 * [__construct description]
	 * @param [type] $report           [description]
	 * @param [type] $reportproperties [description]
	 */
	public function __construct($report, $reportproperties) {
		parent::__construct($report);
		$this->parent = false;
		$this->courselevel = true;
		$this->components = array('columns', 'filters', 'permissions', 'plot');
		$columns = ['total', 'inprogress', 'notyetstarted', 'completed', 'totaltimespent', 'numviews','submitted','highestgrade','lowestgrade'];
		$this->columns = ['userfield' => array('userfield'), 'userassignments' => $columns];
		$this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
        $this->filters = array('users');
        $this->orderable = array('fullname', 'notyetstarted', 'inprogress', 'completed', 'totaltimespent', 'numviews', 'submitted', 'highestgrade', 'lowestgrade');
        if(!isset($this->params['filter_courses'])){
            $this->initial_basicparams('courses');
            $coursefilter = array_keys($this->filterdata);
            $this->params['filter_courses'] = array_shift($coursefilter);
        }
	}
	/**
	 * [get_all_elements description]
	 * @return [type] [description]
	 */
	public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
		global $DB, $USER;

		if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        } 
        if (!isset($this->params['filter_courses']) && $this->params['filter_courses'] > SITEID) {
            $this->initial_basicparams('courses');
            $coursefilter = array_keys($this->filterdata);
            $this->params['filter_courses'] = array_shift($coursefilter);
        }
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
    		$basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams AS $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
		$userid = isset($this->params['filter_users']) ? $this->params['filter_users'] : array();
		$params = array();
		$concatsql = "";
		$coursefiltersql = "";
		if (isset($this->search) && $this->search) {
			$fields = array("CONCAT(u.firstname, ' ', u.lastname)", "u.email");
			$fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
			$fields .= " LIKE '%" . $this->search . "%' ";
			$concatsql .= " AND ($fields) ";
		}
		if (!empty($userid) && $userid != '_qf__force_multiselect_submission') {
			is_array($userid) ? $userid = implode(',', $userid) : $userid;
			$concatsql .= " AND u.id IN ($userid)";
		}
		if ($this->params['filter_courses'] > SITEID) {
			$courseid = $this->params['filter_courses'];
			$concatsql .= " AND c.id IN ($courseid)";
			$coursefiltersql .= " AND cm.course IN ($courseid)";
			$submittedsql = " AND a.course IN ($courseid)";
			$notyetstartedsql = " AND e.courseid IN ($courseid)";
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
        if ($this->params['filter_users'] > 0) {
            $userid = $this->params['filter_users'];
            $concatsql .= " AND u.id = $userid";
        }
      
		if ($this->conditionsenabled) {
            $conditions = implode(',', $conditionfinalelements);
            if(empty($conditions)) {
                return array(array(), 0);
            }
            $concatsql .= " AND u.id IN ( $conditions )";
        }

		if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $concatsql .= " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
		$studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
		$coursesql  = (new querylib)->get_learners('u.id', '');
		$usercountsql = "SELECT COUNT(DISTINCT u.id) ";
		$usersql = "SELECT DISTINCT u.id AS userid, CONCAT(u.firstname,' ', u.lastname) AS fullname,  c.category AS categoryid";
		if (in_array('total', $this->selectedcolumns)) {
			$usersql .= ", 'total' ";
		}
		if (in_array('inprogress', $this->selectedcolumns)) {
			$usersql .= ", (SELECT count(DISTINCT cm.id)
		                       FROM {course_modules} AS cm
		                       JOIN {course} AS c ON c.id = cm.course
		                       JOIN {modules} AS m ON m.id = cm.module
		                       JOIN {assign_submission} AS asub on asub.assignment = cm.instance
		                        AND asub.status = 'submitted'
		                      WHERE cm.visible = 1 AND asub.userid=u.id AND c.visible = 1 AND m.name = 'assign'
		                        AND cm.instance NOT IN
		                        	(SELECT cm.instance
									   FROM {course_modules} AS cm
			                           JOIN {course} AS c ON c.id = cm.course
			                           JOIN {modules} AS m ON m.id = cm.module
			                           JOIN {course_modules_completion} AS cmc ON cmc.coursemoduleid = cm.id
			                          WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1 AND m.name = 'assign'
			                            AND  cmc.userid = u.id ) $coursefiltersql) AS inprogress";
        }
		if (in_array('notyetstarted', $this->selectedcolumns)) {
			$usersql .= ", (SELECT count(DISTINCT cm.instance)
	                          FROM {course_modules} AS cm
	                          JOIN {modules} AS m ON m.id = cm.module
	                         WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND m.name = 'assign'
	                           AND cm.course IN ( SELECT DISTINCT c.id FROM {course} c
													JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
													JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
													JOIN {role_assignments} ra ON ra.userid = ue.userid
													JOIN {role} r ON r.id =ra.roleid AND r.shortname = 'student'
												   WHERE ra.userid= u.id AND c.visible = 1 $notyetstartedsql)
	                           AND cm.instance NOT IN ( SELECT assignment FROM {assign_submission} asub
	                           					  		 WHERE asub.status = 'submitted' AND asub.userid = u.id)
	                           AND cm.instance NOT IN (SELECT cm.instance
												  		 FROM {course_modules} AS cm
					                          			 JOIN {course} AS c ON c.id = cm.course
						                          		 JOIN {modules} AS m ON m.id = cm.module
						                          		 JOIN {course_modules_completion} AS cmc
						                          		   ON cmc.coursemoduleid = cm.id
						                         	    WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1
						                         	      AND m.name = 'assign'
						                            	  AND  cmc.userid = u.id AND cmc.completionstate <> 0 $coursefiltersql
						                            	  ) $coursefiltersql) AS notyetstarted ";
		}
	  	if (in_array('completed', $this->selectedcolumns)) {
			$usersql .= ", ( SELECT count(cmc.id)
							   FROM {course_modules} AS cm
	                           JOIN {course} AS c ON c.id = cm.course
	                           JOIN {modules} AS m ON m.id = cm.module
	                           JOIN {course_modules_completion} AS cmc ON cmc.coursemoduleid = cm.id
	                          WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND m.name = 'assign'
	                            AND  cmc.userid = u.id  AND c.visible = 1 AND cmc.completionstate > 0
	                            $coursefiltersql) AS completed ";
        }
        if (in_array('submitted', $this->selectedcolumns)) {
			$usersql .= ", (SELECT count(sub.id) FROM {assign_submission} AS sub
								JOIN {assign} a ON a.id = sub.assignment
							  WHERE sub.status='submitted' AND u.id = sub.userid $submittedsql) AS submitted";
		}
		if (in_array('highestgrade', $this->selectedcolumns)) {
			$usersql .= ", (SELECT ROUND(MAX(gg.finalgrade),2) FROM {grade_grades} AS gg
							JOIN {grade_items} AS gi ON gg.itemid = gi.id
							JOIN {course_modules} AS cm ON gi.iteminstance = cm.instance
							WHERE gi.itemmodule = 'assign' AND gg.userid = u.id $coursefiltersql) AS highestgrade";
		}
		if (in_array('lowestgrade', $this->selectedcolumns)) {
			$usersql .= ", (SELECT ROUND(MIN(gg.finalgrade),2) FROM {grade_grades} AS gg
							JOIN {grade_items} AS gi ON gg.itemid = gi.id
							JOIN {course_modules} AS cm ON gi.iteminstance = cm.instance
							WHERE gi.itemmodule = 'assign' AND gg.userid = u.id $coursefiltersql) AS lowestgrade";
		}
		if (in_array('totaltimespent', $this->selectedcolumns)) {
			$usersql .= ", (SELECT SUM(mt.timespent) from {block_ls_modtimestats} AS mt JOIN {course_modules} cm ON cm.id = mt.activityid JOIN {modules} m ON m.id = cm.module WHERE m.name = 'assign' AND mt.userid = u.id $coursefiltersq ) AS totaltimespent";
		}
		if (in_array('numviews', $this->selectedcolumns)) {
			$usersql .= ", (SELECT COUNT(DISTINCT lsl.userid)  FROM {logstore_standard_log} lsl JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid JOIN {modules} m ON m.id = cm.module WHERE m.name = 'assign' AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.anonymous = 0 AND lsl.userid = u.id $coursefiltersql) AS distinctusers,
                    (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid JOIN {modules} m ON m.id = cm.module WHERE m.name = 'assign' AND lsl.crud = 'r' AND lsl.contextlevel = 70  AND lsl.anonymous = 0 AND lsl.userid = u.id $coursefiltersql ) AS numviews";
		}
		$sql = "  FROM {course} AS c
                      JOIN {enrol} AS e ON c.id = e.courseid AND e.status = 0
                      JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.status = 0
                      JOIN {role_assignments} AS ra ON ra.userid = ue.userid
                      JOIN {context} con ON c.id = con.instanceid
                      JOIN {role} AS rl ON rl.id = ra.roleid AND rl.shortname = 'student'
                      JOIN {user} AS u ON u.id = ue.userid
                     WHERE  c.visible = 1 AND ra.roleid = :roleid AND ra.contextid =con.id
                     AND u.confirmed = 1 AND u.deleted = 0
                      $concatsql";
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
			$usercount = $DB->count_records_sql($usercountsql . $sql, $params);
		} catch (dml_exception $e) {
			$usercount = 0;
		}
		if (!empty($this->sqlorder)) {
            $sql .=" ORDER BY ". $this->sqlorder;
        } else {
        	 if(!empty($sqlorder)){
                $sql .= " ORDER BY u.$sqlorder ";
            } else {
            	$sql .= " ORDER BY ue.id DESC";
            }
        }
		try {
			$users = $DB->get_records_sql($usersql . $sql, $params, $this->start, $this->length);
		} catch (dml_exception $e) {
			$users = array();
		}
		return array($users, $usercount);
	}
	/**
	 * [get_rows description]
	 * @param  array  $users [description]
	 * @return [type]        [description]
	 */
	public function get_rows($users = array()) {
		return $users;
	}
}