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


class report_timespent extends reportbase implements report {

	/**
	 * [__construct description]
	 * @param [type] $report           [description]
	 * @param [type] $reportproperties [description]
	 */
	public function __construct($report, $reportproperties = false) {
		parent::__construct($report);
		$this->parent = false;
   		$this->courselevel = true;
		$this->components = array('columns', 'filters', 'permissions', 'plot');
		$timespentcolumns = array('totaltimespent');
		$this->columns = ['userfield' => ['userfield'] , 'timespentcolumns' => $timespentcolumns];
		$this->basicparams = array(['name' => 'coursecategories']);
		$this->filters = array('courses');
		$this->orderable = array('totaltimespent','fullname','email');


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
		if (isset($this->search) && $this->search) {
			$fields = array("CONCAT(u.firstname, ' ', u.lastname)", "u.email");
			$fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
			$fields .= " LIKE '%" . $this->search . "%' ";
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
		if (isset($this->params['filter_courses']) && $this->params['filter_courses'] > SITEID) {
			$filter_courses = $this->params['filter_courses'];
			$concatsql .= " AND lcts.courseid IN ($filter_courses)";
		}
		if ($this->role == 'student') {
            return array(array(), 0);
        }
		if((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
			$userrole = $this->role;
			if($userrole == 'teacher' || $userrole == 'editingteacher' || $userrole == 'student' || $userrole == 'instructor') {
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
					$concatsql .= " AND lcts.courseid IN ($courseid)  ";
				}
				if($userrole == 'student') {
					$concatsql .= " AND lcts.userid = :userid ";
					$params['userid'] = $this->userid;
				}
			} else {
				return array(array(), 0);
			}
		}
		if ($this->ls_startdate >= 0 && $this->ls_enddate) {
			$concatsql .= " AND lcts.timemodified BETWEEN :ls_startdate AND :ls_enddate ";
			$params['ls_startdate'] = $this->ls_startdate;
			$params['ls_enddate'] = $this->ls_enddate;
		}
		$uniquepopularresourcescount = "SELECT COUNT(DISTINCT lcts.userid) ";
		$uniquepopularresources = "SELECT DISTINCT lcts.userid, lcts.courseid, CONCAT(u.firstname, ' ', u.lastname) AS fullname,  c.category AS categoryid";
		if (in_array('totaltimespent', $this->selectedcolumns)) {
            $uniquepopularresources .= ", SUM(lcts.timespent) AS totaltimespent";
        }
		$sql = " FROM {block_ls_coursetimestats} lcts
				 JOIN {user} u ON u.id = lcts.userid
                 JOIN {course} c ON c.id = lcts.courseid
				 WHERE u.confirmed = 1 AND u.deleted = 0 AND u.id > 2  ";
		$sql .= " $concatsql ";
		try {
			$popularresourcescount = $DB->get_field_sql($uniquepopularresourcescount . $sql, $params);
		} catch (dml_exception $ex) {
			$popularresourcescount = 0;
		}
		$sql .= " GROUP BY lcts.userid ";
		try {
            if(!empty($this->sqlorder)){
                $sql .=" ORDER BY ". $this->sqlorder;
            } else {
            	$sql .= " ORDER BY totaltimespent DESC";
            }
			$popularresources = $DB->get_records_sql($uniquepopularresources . $sql, $params, $this->start, $this->length);
		} catch (dml_exception $ex) {
			$popularresources = array();
		}
		return array($popularresources, $popularresourcescount);
	}
	/**
	 * [get_rows description]
	 * @param  [type] $elements [description]
	 * @return [type]           [description]
	 */
	function get_rows($elements) {
		return $elements;
	}
}
