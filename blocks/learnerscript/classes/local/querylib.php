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
 * @author: Arun Kumar M <arun@eabyas.in>
 * @date: 2017
 */
namespace block_learnerscript\local;
require_once "$CFG->dirroot/enrol/locallib.php";
require_once $CFG->libdir . '/coursecatlib.php';
use coursecat;
class querylib {
	/**
	 * [get_coursescore description]
	 * @param  [type] $userid          [description]
	 * @param  string $courseid_or_ids [description]
	 * @return [type]                  [description]
	 */
	public function get_coursescore($userid, $courseid_or_ids = '') {
		GLOBAL $DB;
		$concatsql = "";
		if (!empty($courseid_or_ids)) {
			$concatsql = " FIND_IN_SET(gi.courseid, :courseid_or_ids)  AND ";
			$params['courseid_or_ids'] = $courseid_or_ids;
		}
		$coursescoresql = "SELECT gg.id, sum(gg.finalgrade) AS score
                             FROM {grade_grades} AS gg
                             JOIN {grade_items} AS gi ON gi.id = gg.itemid
                             JOIN {course_completions} AS cc ON cc.userid = gg.userid
                            WHERE $concatsql gi.itemtype = :itemtype AND gg.userid = :userid
                                    AND gg.finalgrade IS NOT NULL
                                    AND cc.course = gi.courseid AND cc.timecompleted IS NOT NULL
                         GROUP BY gg.userid";
		$params['userid'] = $userid;
		$params['itemtype'] = 'course';
		try {
			$coursescore = $DB->get_record_sql($coursescoresql, $params);
		} catch (dml_exception $ex) {
			print_error("Sql Query Wrong!");
		}
		return $coursescore;
	}
	/**
	 * [get_totalscore description]
	 * @param  [type] $userid          [description]
	 * @param  string $courseid_or_ids [description]
	 * @return [type]                  [description]
	 */
	public function get_totalscore($userid, $courseid_or_ids = '') {
		GLOBAL $DB;
		$concatsql = "";
		if (!empty($courseid_or_ids)) {
			$concatsql = " FIND_IN_SET(gi.courseid, :courseid_or_ids) AND ";
			$params['courseid_or_ids'] = $courseid_or_ids;
		}
		$totalscoresql = " SELECT gg.id, sum(gi.grademax) AS maxscore, gi.grademax
                             FROM {grade_grades} AS gg
                             JOIN {grade_items} AS gi ON gi.id = gg.itemid
                             JOIN {course_completions} AS cc ON cc.userid = gg.userid
                            WHERE $concatsql gg.userid = :userid AND gi.itemtype = :itemtype
                            AND cc.course = gi.courseid  AND cc.timecompleted IS NOT NULL";
		$params['userid'] = $userid;
		$params['itemtype'] = 'course';
		try {
			$totalscore = $DB->get_record_sql($totalscoresql, $params);
		} catch (dml_exception $ex) {
			print_error("Sql Query Wrong!");
		}
		return $totalscore;
	}
	/**
	 * [get_score description]
	 * @param  [type] $courseid_or_ids [description]
	 * @param  string $order           [description]
	 * @return [type]                  [description]
	 */
	public function get_score($courseid_or_ids, $userid = null) {
		GLOBAL $DB;
		$highestscoresql = " SELECT gg.finalgrade AS score
                               FROM {grade_grades} AS gg
                               JOIN {grade_items} AS gi ON gi.id = gg.itemid
                               JOIN {course_completions} AS cc ON cc.course = gi.courseid
                              WHERE gi.courseid in($courseid_or_ids) AND gi.itemtype = :itemtype
                                    AND cc.course = gi.courseid AND cc.timecompleted IS NOT NULL";
  		if($userid){
        	$highestscoresql .= " AND gg.userid = cc.userid AND cc.userid=$userid";
        }
            // $highestscoresql .= " ORDER BY gg.finalgrade $order LIMIT 0,1";
		// $params['courseid_or_ids'] = $courseid_or_ids;
		$params['itemtype'] = 'course';
		try {
			$highestscore = $DB->get_fieldset_sql($highestscoresql, $params);
		} catch (dml_exception $ex) {
			print_error("Sql Query Wrong!");
		}
		return $highestscore;
	}
	/**
	 * [get_aggregate description]
	 * @param  [type] $courseid_or_ids [description]
	 * @return [type]                  [description]
	 */
	public function get_aggregate($courseid_or_ids,$userid) {
		GLOBAL $DB;
		$aggregatesql = "SELECT (gg.finalgrade / gi.grademax)*100  AS aggregate
                           FROM {grade_grades} AS gg
                           JOIN {grade_items} AS gi ON gi.id = gg.itemid
                           JOIN {course_completions} AS cc ON cc.course = gi.courseid
                          WHERE gi.courseid in($courseid_or_ids) AND gi.itemtype = :itemtype
                            AND cc.course = gi.courseid AND cc.timecompleted IS NOT NULL
                            AND gg.userid = cc.userid AND cc.userid=$userid";
		// $params['courseid_or_ids'] = $courseid_or_ids;
		$params['itemtype'] = 'course';
		try {
			$aggregate = $DB->get_fieldset_sql($aggregatesql, $params);
		} catch (dml_exception $ex) {
			print_error("Sql Query Wrong!");
		}
		return $aggregate;
	}
	/**
	 * List of Enrolled Courses for a Particular RoleWise User
	 * @param  integer  $userid      User ID for Particular user
	 * @param  string  $role         Role ShortName
	 * @param  integer $courseid     Course ID for exception particular course
	 * @param  string  $searchconcat Search Value
	 * @param  string  $concatsql    Sql Conditions
	 * @param  string  $limitconcat  Limit 0, 10 like....
	 * @param  boolean $count        Count for get results count or list of records
	 * @param  boolean $check        Check that user has role in LMS
	 * @return integer|Object              If $count true, returns courses count or returns Enrolled Cousrse as per role for that user
	 */
	public function get_rolecourses($userid, $role, $courseid = SITEID, $concatsql = '', $limitconcat = '', $count = false, $check = false, $datefiltersql = '', $menu = false) {
		GLOBAL $DB;
		$params = array('courseid' => $courseid);
		$params['contextlevel'] = CONTEXT_COURSE;
		$params['userid'] = $userid;
		$params['userid1'] = $params['userid'];
		$params['role'] = $role;
		$params['active'] = ENROL_USER_ACTIVE;
		$params['enabled'] = ENROL_INSTANCE_ENABLED;
		$params['now1'] = round(time(), -2); // improves db caching
		$params['now2'] = $params['now1'];
		if ($count) {
			$coursessql = "SELECT COUNT(c.id) as totalcount ";
		} else {
			$coursessql = "SELECT c.id, c.fullname, FROM_UNIXTIME(c.timecreated) as timecreated ";
		}
		$coursessql .= " FROM {course} AS c
                         JOIN (SELECT DISTINCT e.courseid
                                 FROM {enrol} AS e
                                 JOIN {user_enrolments} AS ue ON (ue.enrolid = e.id AND ue.userid = :userid1)
                                WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND
                                (ue.timeend = 0 OR ue.timeend > :now2) $datefiltersql) en ON (en.courseid = c.id)
                   LEFT JOIN {context} AS ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                        JOIN {role_assignments} AS ra ON ra.contextid = ctx.id
                        JOIN {role} AS r ON r.id = ra.roleid
                       WHERE c.id <> :courseid AND c.visible = 1 AND ra.userid = :userid AND r.shortname = :role
                       $concatsql ORDER BY c.id ASC $limitconcat";
		try {
			if ($count) {
				$courses = $DB->count_records_sql($coursessql, $params);
			} else {
				if ($menu) {
					$courses = $DB->get_records_sql_menu($coursessql, $params);
				} else {
					$courses = $DB->get_records_sql($coursessql, $params);
				}
			}
		} catch (dml_exception $ex) {
			print_error("Sql Query Wrong!");
		}
		if ($check) {
			return !empty($courses) ? true : false;
		}
		return $courses;
	}
	/**
	 * [activecourseusers description]
	 * @param  string $role [description]
	 * @return [type]       [description]
	 */
	public function activecourseusers($role = 'student') {
		GLOBAL $DB;

		$params['contextlevel'] = CONTEXT_COURSE;
		$params['role'] = $role;
		$params['noofdays'] = 30;
		$activecourseuserssql = "SELECT DISTINCT l.userid, u.*
                                   FROM {course} AS c
                                   JOIN {context} AS ctx ON  ctx.instanceid = c.id
                                   JOIN {role_assignments} AS ra ON ra.contextid = ctx.id
                                   JOIN {user_lastaccess} AS l ON ra.userid = l.userid
                                   JOIN {role} AS r ON r.id = ra.roleid
                                   JOIN {user} AS u ON u.id = l.userid
                                  WHERE r.shortname = :role  AND ctx.contextlevel = :contextlevel
                                        AND  l.timeaccess > (unix_timestamp() - ((60*60*24)*:noofdays)) ";
		try {
			$activecourseusers = $DB->get_records_sql($activecourseuserssql, $params);
		} catch (dml_exception $ex) {
			print_error("Sql Query Wrong!");
		}
		return $activecourseusers;
	}

	public function filter_get_courses($pluginclass, $selectoption = true) {
		global $DB, $USER;
		if ($pluginclass->report->type != 'sql') {
			if (is_siteadmin() || $_SESSION['role'] == 'manager') {
				$courselist = array_keys($DB->get_records_sql("SELECT * FROM {course} WHERE visible = 1"));
			} else if (!empty($_SESSION['role'])) {
				$allcourses = $this->get_rolecourses($USER->id, $_SESSION['role']);
				$courselist = array_keys($allcourses);
			} else {
				$courselist = array_keys(enrol_get_users_courses($USER->id));
			}
		} else {
			$courselist = array_keys($DB->get_records_sql("SELECT * FROM {course} WHERE visible = 1"));
		}

		$courseoptions = array();
		if($selectoption){
			$courseoptions[0] = $pluginclass->singleselection ? get_string('filter_course', 'block_learnerscript') :
			get_string('select') . ' ' . get_string('course');
		}

		if (!empty($courselist)) {
			list($usql, $params) = $DB->get_in_or_equal($courselist);
			$courses = $DB->get_records_select('course', "id $usql", $params);
			foreach ($courses as $c) {
				if ($c->id == SITEID) {
					continue;
				}
				$courseoptions[$c->id] = format_string($c->fullname);
			}
		}
		return $courseoptions;
	}

	public function filter_get_category($pluginclass, $selectoption = true) {
			global $DB, $USER;
			if ($pluginclass->report->type != 'sql') {
				if (is_siteadmin() || $_SESSION['role'] == 'manager') {
					$categorylist = coursecat::make_categories_list();
					//$categorylist = $DB->get_records_sql("SELECT id, name FROM {course_categories}");
				} else {
					$categorylist = $DB->get_records_sql("SELECT id, name FROM {course_categories} WHERE id IN (SELECT c.category FROM {course} c JOIN {enrol} e ON e.courseid = c.id
					JOIN {user_enrolments} ue ON ue.enrolid = e.id
					WHERE ue.userid = $USER->id)");
				}
			} else {
				$categorylist = array_keys($DB->get_records_sql("SELECT * FROM {course_categories}"));
			}

        $categoryoptions = array();
		if($selectoption){
			$categoryoptions[0] = $pluginclass->singleselection ? get_string('filtercoursecategories', 'block_learnerscript') :
			get_string('select') . ' ' . get_string('category');
		}
        if (!empty($categorylist)) {
        	if (is_siteadmin() || $_SESSION['role'] == 'manager') {
				foreach ($categorylist as $key => $coursecategorie) {
				     $categoryoptions[$key] = $coursecategorie;          
				}
			} else {
				foreach ($categorylist as $coursecategorie) {
					$categoryoptions[$coursecategorie->id] = $coursecategorie->name;
				}
			}	
        }
        return $categoryoptions;
	}

	public function filter_get_users($pluginclass, $selectoption = true) {
        global $DB,$USER;
		if ($pluginclass->report->type != 'sql') {
			$components = (new \block_learnerscript\local\ls)->cr_unserialize($pluginclass->report->components);
			if (!empty($components['conditions']['elements'])) {
				$conditions = $components['conditions'];
				$reportclassname = 'report_' . $pluginclass->report->type;
				$properties = new \stdClass();
				$reportclass = new $reportclassname($pluginclass->report, $properties);
				$userslist = $reportclass->elements_by_conditions($conditions);
			} else {
				if(!is_siteadmin() && $_SESSION['role'] != 'manager'){
					$courses = $this->get_rolecourses($USER->id, $_SESSION['role']);
					if(!empty($courses)){
						$courselist = implode(',', array_keys($courses));
						 $sql = "SELECT DISTINCT u.*
					 			  FROM {course} AS c
			                      JOIN {enrol} AS e ON c.id = e.courseid
			                      JOIN {user_enrolments} AS ue ON ue.enrolid = e.id
			                      JOIN {role_assignments} AS ra ON ra.userid = ue.userid
			                      JOIN {user} AS u ON u.id = ue.userid
			                      WHERE c.id in($courselist) AND u.deleted = 0 ";
				        $userslist = $DB->get_records_sql($sql);
			         }
				}else{
					$sql = " SELECT * FROM {user} as u WHERE id > 2 AND u.deleted = 0 " ;
					$userslist = $DB->get_records_sql($sql);
				}

			}
		} else {
			$sql = " SELECT * FROM {user} as u WHERE id > 2 AND u.deleted = 0 " ;
				$userslist = $DB->get_records_sql($sql);
		}

		$usersoptions = array();
		if($selectoption){
			$usersoptions[0] = $pluginclass->singleselection ? get_string('filter_user', 'block_learnerscript') :
	                                                             get_string('select') . ' ' . get_string('users');
		}
		if (!empty($userslist)) {
			foreach ($userslist as $c) {
				$usersoptions[$c->id] = fullname($c);
			}
		}
        return $usersoptions;
	}
	public function get_learners($useroperatorsql = '', $courseoperatorsql = ''){

		if(empty($courseoperatorsql) && empty($useroperatorsql)){
			return false;
		}
		if(!empty($useroperatorsql)){
			$sql = " SELECT c.id ";
		}
		if(!empty($courseoperatorsql)){
			$sql = " SELECT ue.userid ";
		}
		$sql .= " FROM {course} c
				  JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
				  JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
				  JOIN {role_assignments}  ra ON ra.userid = ue.userid
				  JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
				  JOIN {context} ctx ON ctx.instanceid = c.id
				  AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1";
		if(!empty($courseoperatorsql)){
			$sql .= " WHERE c.id = $courseoperatorsql";
		}
		if(!empty($useroperatorsql)){
			$sql .= " WHERE ra.userid = $useroperatorsql";
		}
		return $sql;
	}
}