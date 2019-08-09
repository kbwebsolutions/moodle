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
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

class report_usercourses extends reportbase implements report {

    public function __construct($report, $reportproperties) {
        parent::__construct($report);
		$this->components = array('columns','ordering', 'filters', 'permissions', 'calcs', 'plot');
	 	$columns = ['timeenrolled', 'status','grade','totaltimespent', 'progressbar','completedassignments','completedquizzes', 'completedscorms', 'marks', 'badgesissued', 'completedactivities'];
        $this->columns = ['userfield'=>['userfield'],'usercoursescolumns' => $columns];
        $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
   		$this->parent = false;
   		$this->courselevel = true;
		$this->filters = array('users');
		$this->orderable = array('fullname', 'timeenrolled', 'completedassignments', 'completedquizzes', 'completedscorms', 'completedactivities', 'progressbar', 'marks', 'grade', 'badgesissued', 'totaltimespent');
	}
	public function get_all_elements($sqlorder = '') {
		global $DB, $USER, $COURSE;

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
		$this->courseid = $this->params['filter_courses'];

		$context = context_course::instance($this->courseid);
		$concatsql = '';
		//Filtering
        if (isset($this->search) && $this->search) {
            $fields = array("CONCAT(u.firstname, ' ', u.lastname)", "u.email");
            $fields = implode(" LIKE :search1 OR ", $fields);
            $fields .= " LIKE :search2 ";
            $params['search1'] = '%' . $this->search . '%';
            $params['search2'] = '%' . $this->search . '%';
            $concatsql .= " AND ($fields) ";
        }
        if (!empty($this->params['filter_courses'])) {
			$courseid = $this->params['filter_courses'];
			$concatsql .= " AND c.id IN ($courseid)";
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
		if(isset($this->params['filter_users']) && $this->params['filter_users'] > 0) {
			$concatsql .= " AND u.id = :filter_users";
			$params['filter_users'] = $this->params['filter_users'];
		}
 		if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $datefiltersql = " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        $status = isset($this->params['filter_status']) ? $this->params['filter_status'] : '';
        if ($this->role == 'student') {
            return array(array(), 0);
        }
		list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED,'relatedctx');

		$completeduserssql = "SELECT  u.id as userid,CONCAT(u.firstname,' ',u.lastname) AS fullname, u.email,  cc.timestarted as timestarted, u.timezone,
			cc.timecompleted as timecompleted, cc.course as courseid, c.category AS categoryid ";
		if (in_array('timeenrolled', $this->selectedcolumns)) {
            $completeduserssql .= ", e.timecreated AS timeenrolled";
        }
        if (in_array('grade', $this->selectedcolumns)) {
            $completeduserssql .= ", (SELECT ROUND(((gg.finalgrade/gi.grademax)*100),2) 
            							FROM {grade_items} gi
				    				    JOIN {grade_grades} gg ON gg.itemid = gi.id AND gi.itemtype = 'course'
				    				    WHERE gg.userid = u.id AND gi.courseid = $this->courseid) AS grade";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $completeduserssql .= ", (SELECT SUM(bt.timespent) from {block_ls_coursetimestats} as bt WHERE bt.userid = u.id AND bt.courseid = $this->courseid) as totaltimespent";
        }
        if (in_array('progressbar', $this->selectedcolumns)) {
            $completeduserssql .= ", ROUND(((SELECT count(cm.id)
				      					FROM {course_modules} AS cm
				      					JOIN {modules} AS m ON m.id = cm.module
				      					JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id
				    				   WHERE  cm.visible = 1 AND cm.deletioninprogress = 0 AND cm.course = $this->courseid
				    				     AND cmc.userid = u.id)/(SELECT count(cm.id)
				      					FROM {course_modules} AS cm
				      					JOIN {modules} AS m ON m.id = cm.module
				    				   WHERE  cm.deletioninprogress = 0 AND cm.course = $this->courseid))*100,2) AS progressbar";
        }
        if (in_array('completedassignments', $this->selectedcolumns)) {
            $completeduserssql .= ", (SELECT count(cm.id)
				      					FROM {course_modules} AS cm
				      					JOIN {modules} AS m ON m.id = cm.module
				      					JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id
				    				   WHERE m.name = 'assign' AND cm.visible = 1 AND cm.deletioninprogress = 0
				    					 AND cm.course = $this->courseid AND cmc.userid = u.id AND cmc.completionstate != 0) AS completedassignments";
        }
        if (in_array('completedquizzes', $this->selectedcolumns)) {
            $completeduserssql .= ", (SELECT count(cm.id)
				      					FROM {course_modules} AS cm
				      					JOIN {modules} AS m ON m.id = cm.module
				      					JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id
				    				   WHERE m.name = 'quiz' AND cm.visible = 1 AND cm.deletioninprogress = 0
				    					 AND cm.course = $this->courseid AND cmc.userid = u.id AND cmc.completionstate != 0) AS completedquizzes";
        }
        if (in_array('completedscorms', $this->selectedcolumns)) {
            $completeduserssql .= ", (SELECT count(cm.id)
				      					FROM {course_modules} AS cm
				      					JOIN {modules} AS m ON m.id = cm.module
				      					JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id
				    				   WHERE m.name = 'scorm' AND cm.visible = 1 AND cm.deletioninprogress = 0
				    					AND cm.course = $this->courseid AND cmc.userid = u.id AND cmc.completionstate != 0) AS completedscorms";
        }
        if (in_array('marks', $this->selectedcolumns)) {
            $completeduserssql .= ", (SELECT ROUND(gg.finalgrade, 2) FROM {grade_items} gi
				    				     JOIN {grade_grades} gg ON gg.itemid = gi.id AND gi.itemtype = 'course'
				    				    WHERE gg.userid = u.id AND gi.courseid = $this->courseid) AS marks";
        }
        if (in_array('badgesissued', $this->selectedcolumns)) {
            $completeduserssql .= ", (SELECT count(bi.id) FROM {badge_issued} as bi
				                        JOIN {badge} as b ON b.id = bi.badgeid
				                       WHERE  bi.visible = 1 AND b.status != 0
				                         AND b.status != 2 AND b.courseid =$this->courseid
				                         AND bi.userid = u.id ) as badgesissued";
        }
        if (in_array('completedactivities', $this->selectedcolumns)) {
            $completeduserssql .= ", (SELECT count(cm.id)
				      					FROM {course_modules} AS cm
				      					JOIN {modules} AS m ON m.id = cm.module
				      					JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id
				    				   WHERE  cm.visible = 1 AND cm.deletioninprogress = 0 AND cm.course = $this->courseid
				    				     AND cmc.userid = u.id AND cmc.completionstate != 0) AS completedactivities";
        }
				    				   
				    				    
		$completeduserssql .= " FROM {user} u 
						LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
                 				LEFT JOIN {enrol} e1 ON e1.id = ue.enrolid
                 				LEFT JOIN {course} c ON c.id = e1.courseid
							    JOIN (SELECT DISTINCT eu1_u.id, ej1_ue.timecreated
							    	   FROM {user} eu1_u
							    	   JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
							    	   JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = :ej1_courseid)
							           WHERE 1 = 1 AND ej1_ue.status = :ej1_active AND ej1_e.status = :ej1_enabled AND ej1_ue.timestart < :ej1_now1 AND (ej1_ue.timeend = 0 OR ej1_ue.timeend > :ej1_now2) AND eu1_u.deleted = 0) e ON e.id = u.id
							         LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)";

		
		$completeduserssql .= " LEFT JOIN {course_completions} as cc ON cc.userid = u.id AND cc.course = $this->courseid";

		$completeduserssql .= "	WHERE u.id IN (SELECT ra.userid
											     FROM {role_assignments} ra
											    WHERE ra.roleid = :roleid AND ra.contextid $relatedctxsql $datefiltersql)
												  AND u.id > 2 AND u.confirmed = 1 AND u.deleted = 0";
		$completeduserssql .= " $concatsql";
        if ($status == 'completed') {
            $completeduserssql .= "AND u.id IN (SELECT userid FROM {course_completions}
                                    WHERE course=$this->courseid AND timecompleted IS NOT NULL)";
        }
        $params['contextlevel'] = CONTEXT_COURSE;
		$params['userid'] = $this->userid;
		$params['ej1_active'] = ENROL_USER_ACTIVE;
		$params['ej1_enabled'] = ENROL_INSTANCE_ENABLED;
		$params['ej1_now1'] = round(time(), -2); // improves db caching
		$params['ej1_now2'] = $params['ej1_now1'];
		$params['ej1_courseid'] = $this->courseid;
		$params['courseid'] = $this->courseid;
		$params['courseid1'] = $this->courseid;
		$params['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'student'));
		$params = array_merge($params, $relatedctxparams);
		if(!empty($this->sqlorder)){
            $completeduserssql .=" ORDER BY ". $this->sqlorder;
        } else{
           if(!empty($sqlorder)){
	            $completeduserssql .= " ORDER BY u.$sqlorder";
	        } else{
	            $completeduserssql .= " ORDER BY u.id DESC";
	        }
        }
		$rt = $DB->get_records_sql($completeduserssql, $params);
		try {
			$rs = $DB->get_records_sql($completeduserssql, $params, $this->start, $this->length);
		} catch (dml_exception $e){
			$rs = array();
		}
		return array($rs, count($rt));
	}

	public function get_rows($users) {
		return $users;
	}
}