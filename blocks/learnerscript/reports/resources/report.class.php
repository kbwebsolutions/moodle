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

class report_resources extends reportbase implements report {

	/**
	 * [__construct description]
	 * @param [type] $report           [description]
	 * @param [type] $reportproperties [description]
	 */
	public function __construct($report, $reportproperties = false) {
		parent::__construct($report);
		$this->parent = true;
		$this->components = array('columns', 'filters', 'permissions', 'plot');
		$resourcescolumns = array('activity','totaltimespent','numviews');
		$this->columns = ['activityfield' => ['activityfield'] ,'resourcescolumns' => $resourcescolumns];
		$this->basicparams = array(['name' => 'coursecategories']);
		$this->filters = array('courses');
		$this->orderable = array('course','activity','totaltimespent','numviews');
	}
	/**
	 * [get_all_elements description]
	 * @return [type] [description]
	 */
	function get_all_elements() {
		global $DB, $USER;
		$elements = array();
		$params = array();
		$concatsql = '';
		
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
		$modules = $DB->get_fieldset_select('modules',  'name', '');
        foreach ($modules as $modulename) {
        	$resourcearchetype = plugin_supports('mod', $modulename, FEATURE_MOD_ARCHETYPE);
        	if($resourcearchetype){
        		$aliases[] = $modulename;
        		$resources[] = "'$modulename'";
        		$fields1[] = "COALESCE($modulename.name,'')";
        	}
        }
        $resourcenames = implode(',', $fields1);
		if (isset($this->search) && $this->search) {
			$fields1[] = array_push($fields1,"c.fullname");
			$fields = implode(" LIKE '%$this->search%' OR ", $fields1);
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
            $courseids = $this->params['filter_courses'];
            $concatsql .= " AND c.id IN ($courseids) ";
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
		if((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
			$userrole = $this->role;
			if($userrole == 'teacher' || $userrole == 'editingteacher' || $userrole == 'student') {
				$mycourses = (new querylib)->get_rolecourses($this->userid, $userrole, SITEID, '', '', false);
				$courseids = array();
				if(empty($mycourses)) {
					return array(array(), 0);
				}
				foreach ($mycourses as $course) {
					$courseids[] = $course->id;
				}
				$courseid = implode(',', $courseids);
				if (!empty($courseid)) {
					$concatsql .= " AND cm.course IN ( $courseid ) ";
				}
				if($userrole == 'student') {
					$concatsql .= " AND l.userid = :userid ";
					$params['userid'] = $this->userid;
				}
			} else {
				return array(array(), 0);
			}
		}
		if ($this->ls_startdate >= 0 && $this->ls_enddate) {
			$concatsql .= " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
			$params['ls_startdate'] = $this->ls_startdate;
			$params['ls_enddate'] = $this->ls_enddate;
		}

		$uniquepopularresourcescount = "SELECT COUNT(cm.id) ";
		$uniquepopularresources = "SELECT cm.id AS activityid, c.id AS courseid, 
						c.fullname AS course, m.name AS moduletype, m.id AS module, c.category AS categoryid";
		// $resourcenames = implode(',', $fields1);
						
		if (in_array('activity', $this->selectedcolumns)) {
            $uniquepopularresources .= ", CONCAT($resourcenames) AS activity";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $uniquepopularresources .= ", (SELECT SUM(mt.timespent) 
            			FROM {block_ls_modtimestats} AS mt WHERE cm.id = mt.activityid 
            			AND mt.courseid = c.id) AS totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $uniquepopularresources .= ", (SELECT COUNT(DISTINCT lsl.userid)  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND u.confirmed = 1 AND u.deleted = 0) AS distinctusers,
               (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND u.confirmed = 1 AND u.deleted = 0) AS numviews";
        }
		
		$sql = " FROM {course_modules} AS cm
				JOIN {modules} AS m ON m.id = cm.module
				JOIN {course} AS c ON c.id = cm.course ";
			foreach ($aliases as $alias) {
				$sql .= " LEFT JOIN {".$alias."} AS $alias ON $alias.id = cm.instance AND m.name = '$alias'";
			}
		$resourceslist = implode(',', $resources);
		$sql .="WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND m.name IN ($resourceslist)" . $concatsql;
		$params['target'] = 'course_module';
		$params['contextlevel'] = CONTEXT_MODULE;
		$params['action'] = 'viewed';
		try {
		//	$sql .= " GROUP BY l.contextinstanceid ";
			$popularresourcescount = $DB->get_field_sql($uniquepopularresourcescount . $sql , $params);
		} catch (dml_exception $ex) {
			$popularresourcescount = 0;
		}
		try {
		if(!empty($this->sqlorder)){
            $sql .=" order by ". $this->sqlorder;
        }else {
			$sql .= " ORDER BY cm.id DESC ";
        }
			$popularresources = $DB->get_records_sql($uniquepopularresources . $sql, $params,$this->start, $this->length);
		} catch (dml_exception $ex) {
			$popularresources = array();
		}
		return array($popularresources, (int)$popularresourcescount);
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