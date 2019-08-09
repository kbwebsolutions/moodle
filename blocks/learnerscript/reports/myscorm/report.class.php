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
 * @author: Arun Kumar <arun@eabyas.in>
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';

use block_learnerscript\local\reportbase;
use block_learnerscript\local\querylib;

class report_myscorm extends reportbase {

    public function __construct($report, $reportproperties) {
        parent::__construct($report);
        $this->columns = array('myscormcolumns' => array('course', 'scormname', 'attempt' , 'activitystate', 'finalgrade','firstaccess', 'lastaccess', 'totaltimespent', 'numviews'));
        $this->parent = true;
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
        $this->components = array('columns', 'filters', 'permissions', 'calcs', 'plot');
        $this->courselevel = false;
        $this->filters = array('courses');
        $this->orderable = array('course','scormname','activitystate','attempt','finalgrade','totaltimespent','numviews');
	}
	public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
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
        $params = array();
        $elements = array();
        $this->courseid = isset($this->params['filter_courses']) ? $this->params['filter_courses'] : array();
        $searchconcat = '';
        if (isset($this->search) && $this->search) {
           $fields = array("c.fullname", "s.name");
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $searchconcat = " AND ($fields) ";
        }
        if (!empty($this->params['filter_coursecategories'])) {
            $categoryid = $this->params['filter_coursecategories'];
            $ids = [$categoryid];
            $category = \coursecat::get($categoryid);
            $categoryids = array_merge($ids, $category->get_all_children_ids());
        }
        $catids = implode(',', $categoryids);
        if (!empty($catids)) {
             $searchconcat .= " AND c.category IN ($catids) ";
        }
        if (!empty($this->courseid) && $this->courseid != '_qf__force_multiselect_submission') {
          $courseid = $this->courseid;
          $searchconcat .= " AND c.id = $courseid";
        }
         $params['userid'] = $userid;
		if (!empty($conditionfinalelements)) {
			$conditions = implode(',', $conditionfinalelements);
			$conditioncancatsql = " AND c.id IN ($conditions)";
		} else {
			$conditioncancatsql = "";
		}
        $datefiltersql = '';
        if($this->ls_startdate >= 0 && $this->ls_enddate) {
            $datefiltersql = " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
    $sql = '';
    if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
                $roleshortname = $this->role ;
                if(empty($roleshortname)) {
                    return array(array(), 0);
                }
          $enrolledcourses = (new querylib)->get_rolecourses($this->userid, $roleshortname, SITEID, $searchconcat, '', false);
          $courseids = array();
          foreach ($enrolledcourses as $course) {
            $courseids[] = $course->id;
          }
          $courseids = implode(',', $courseids);
                if(empty($courseids)) {
                    return array(array(), 0);
                }
          if (!empty($courseids)) {
            $sql = " AND c.id IN ($courseids) ";
          }
        }
        $coursesql  = (new querylib)->get_learners('','s.course');
		$countscormactivitiescoursesql = "SELECT COUNT(DISTINCT s.id) ";
        $selectscormactivitiescoursesql = "SELECT DISTINCT s.id, c.id AS courseid, ra.userid AS userid, cm.id as cmid, m.id as moduleid, st.scormid as scormid, c.category AS categoryid";
        if (in_array('course', $this->selectedcolumns)) {
            $selectscormactivitiescoursesql .= ", c.fullname AS course";
        }
        if (in_array('scormname', $this->selectedcolumns)) {
            $selectscormactivitiescoursesql .= ", s.name AS scormname";
        }
        if (in_array('attempt', $this->selectedcolumns)) {
            $selectscormactivitiescoursesql .= ", (SELECT attempt AS attempts  
                    FROM {scorm_scoes_track} WHERE scormid = s.id AND userid = $userid ORDER BY id DESC LIMIT 0,1) AS attempt";
        }
        if (in_array('activitystate', $this->selectedcolumns)) {
            $selectscormactivitiescoursesql .= ", (SELECT value FROM {scorm_scoes_track} WHERE scormid = s.id AND element = 'cmi.core.lesson_status' AND userid = $userid ORDER BY id DESC LIMIT 0,1) AS activitystate";
        }
        if (in_array('finalgrade', $this->selectedcolumns)) {
            $selectscormactivitiescoursesql .= ", (SELECT ROUND(gg.finalgrade, 2) FROM {grade_grades} gg JOIN {grade_items} gi ON gi.id = gg.itemid WHERE gi.itemmodule = 'scorm' AND gi.iteminstance = s.id AND gg.userid = $userid) AS finalgrade";
        }
        if (in_array('firstaccess', $this->selectedcolumns)) {
            $selectscormactivitiescoursesql .= ", (SELECT value FROM {scorm_scoes_track} WHERE userid = $userid AND scormid = s.id AND element = 'x.start.time' ORDER BY attempt ASC LIMIT 0, 1) AS firstaccess";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $selectscormactivitiescoursesql .= ", (SELECT SUM(mt.timespent) FROM {block_ls_modtimestats} as mt WHERE cm.id = mt.activityid  AND mt.courseid = c.id AND mt.userid = $userid) AS totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $selectscormactivitiescoursesql .= ", (SELECT COUNT(DISTINCT lsl.userid)  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.courseid = c.id AND lsl.anonymous = 0 AND lsl.contextinstanceid = cm.id AND lsl.userid = $userid AND u.confirmed = 1 AND u.deleted = 0) AS distinctusers,
                (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE lsl.crud = 'r' AND lsl.contextinstanceid = cm.id AND lsl.contextlevel = 70 AND lsl.courseid = c.id AND lsl.anonymous = 0 AND lsl.userid = $userid AND u.confirmed = 1 AND u.deleted = 0) AS numviews";
        }
        $scormactivitiescoursesql = " FROM {role_assignments} AS ra
                                  JOIN {role} as r on r.id=ra.roleid AND r.shortname='student'
                                  JOIN {context} AS ctx ON ctx.id = ra.contextid
                                  JOIN {course} as c ON c.id = ctx.instanceid
                                  JOIN {scorm} AS s ON s.course = c.id
                                  JOIN {course_modules} AS cm ON cm.instance = s.id
                                  JOIN {modules} AS m ON m.id = cm.module
                                  LEFT JOIN {scorm_scoes_track} AS st ON st.scormid = s.id
                                  JOIN {scorm_scoes} ss ON ss.scorm = s.id
                                  WHERE ra.userid = $userid  AND cm.visible = 1 AND
                                  cm.deletioninprogress = 0 AND c.visible = 1 AND m.name = 'scorm'
                                  AND ra.userid IN ($coursesql)
                                  $searchconcat $conditioncancatsql $sql $datefiltersql ";
		$totlalcormactivitiescourse = $DB->count_records_sql($countscormactivitiescoursesql . $scormactivitiescoursesql);
        $scormactivitiescoursesql .= " GROUP BY s.id ";
        if (!empty($this->sqlorder)) {
            $scormactivitiescoursesql .=" ORDER BY ". $this->sqlorder;
        } else {
            $scormactivitiescoursesql .=" ORDER BY s.id DESC";
        }
		$scormactivitiescourse = $DB->get_records_sql($selectscormactivitiescoursesql . $scormactivitiescoursesql, array(), $this->start, $this->length);

        return array($scormactivitiescourse, $totlalcormactivitiescourse);
	}
	public function get_rows($elements) {
		return $elements;
	}

}