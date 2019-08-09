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
use block_learnerscript\local\querylib;

class report_users extends reportbase {
    /**
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        $this->components = array('columns', 'conditions', 'ordering', 'permissions', 'filters', 'plot');
        $this->parent = true;
        $this->columns = array('userfield' => array('userfield'), 'usercolumns' => array('enrolled', 'inprogress',
            'completed', 'grade', 'badges', 'progress', 'status'));
        $this->orderable = array('fullname', 'email', 'enrolled', 'inprogress', 'completed',
                            'badges', 'progress');
        $this->basicparams = array(['name' => 'coursecategories']);
        $this->filters = array('users');

    }
    /**
     * @param  string  $sqlorder user order
     * @param  array  $conditionfinalelements userids
     * @return array array($users, $usercount) list and count of users
     */
    public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
        global $DB;
        $searchconcat = '';

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
            $searchconcat = " AND ($fields) ";
        }
        $params = array();

        if (isset($this->params['filter_users'])
            && $this->params['filter_users'] >0
            && $this->params['filter_users'] != '_qf__force_multiselect_submission') {
            $userid = $this->params['filter_users'];
            $searchconcat .= " AND u.id IN ($userid) ";
        }
        $enrolleddatesql = '';
        $inprogressdatesql = '';
        $comdatesql = '';
        $quizsql = '';
        $assignsql = '';
        $scormsql = '';
        $gradesql = '';
        $badgesql = '';
        $concatsql = '';
        $concatsql1 = '';
        if (!empty($this->params['filter_coursecategories'])) {
            $categoryid = $this->params['filter_coursecategories'];
            $ids = [$categoryid];
            $category = \coursecat::get($categoryid);
            $categoryids = array_merge($ids, $category->get_all_children_ids());
        }
        $catids = implode(',', $categoryids);
        if (!empty($catids)) {
             $concatsql1 .= " AND c.category IN ($catids) ";
             $concatsql2 .= " AND c.category IN ($catids) ";
        }
        if (isset($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $filter_users = $this->params['filter_users'];
            $concatsql .= " AND u.id = $filter_users";
        }
        if ($this->conditionsenabled) {
            $conditions = implode(',', $conditionfinalelements);
            if (empty($conditions)) {
                return array(array(), 0);
            }
            $searchconcat .= " AND u.id IN ( $conditions )";
        }

        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $inprogressdatesql = " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $comdatesql = " AND cc.timecompleted BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $activitydatesql = " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $badgedatesql = " AND bi.dateissued BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $concatsql = " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate";
        }

        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $learnercoursesql  = (new querylib)->get_learners('u.id','');
        $countsql  = " SELECT count(DISTINCT u.id) ";
        $selectsql = " SELECT DISTINCT u.id AS userid, CONCAT(u.firstname,' ',u.lastname) AS fullname,  c.category AS categoryid";
        if(in_array('enrolled', $this->selectedcolumns)){
            $selectsql .= ", COUNT(DISTINCT c.id) AS enrolled";
        }
        if(in_array('inprogress', $this->selectedcolumns)){
            $selectsql .= ", (COUNT(DISTINCT c.id) - COUNT(DISTINCT cc.id)) AS inprogress";
        }
        if(in_array('completed', $this->selectedcolumns)){
            $selectsql .= ", COUNT(DISTINCT cc.id) AS completed";
        }
        if(in_array('progress', $this->selectedcolumns)){
            $selectsql .= ", ROUND(COUNT(cc.id)/COUNT(DISTINCT c.id)*100, 2) AS progress";
        }
        if (in_array('badges', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT count(bi.id) FROM {badge_issued} as bi
                                JOIN {badge} as b ON b.id = bi.badgeid
                                LEFT JOIN {course} as c ON b.courseid = c.id AND c.visible = 1
                                WHERE  bi.visible = 1 AND b.status != 0
                                 AND b.status != 2 AND b.status != 4 AND bi.userid = u.id $concatsql2 $badgedatesql ) as badges ";
        }
        if (in_array('grade', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT CONCAT(ROUND(sum(gg.finalgrade), 2),' / ', ROUND(sum(gi.grademax), 2))
                               FROM {grade_grades} AS gg
                               JOIN {grade_items} AS gi ON gi.id = gg.itemid
                               JOIN {course_completions} AS cc ON cc.course = gi.courseid
                               JOIN {course} AS c ON cc.course = c.id AND c.visible=1
                              WHERE gi.itemtype = 'course' AND cc.course = gi.courseid
                                AND cc.timecompleted IS NOT NULL AND cc.course IN ($learnercoursesql)
                                AND gg.userid = cc.userid AND cc.userid = u.id $comdatesql) as grade ";
        }
        $fromsql  .= "FROM {user} u
                      JOIN {role_assignments} ra ON ra.userid = u.id
                      JOIN {context} AS ctx ON ctx.id = ra.contextid
                      JOIN {course} c ON c.id = ctx.instanceid
                      JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                      JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.userid = ra.userid AND ue.status = 0
                      JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                 LEFT JOIN {course_completions} cc ON cc.course = ctx.instanceid AND cc.userid = u.id AND cc.timecompleted > 0
                     WHERE c.visible = 1 AND u.confirmed = 1 AND u.deleted = 0 
                     $concatsql1 $searchconcat $concatsql ";
        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            if (!empty($this->role)) {
              $roleshortname = $this->role;
                $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
                if (!empty($mycourses)) {
                    $mycourseids = implode(',', array_keys($mycourses));
                    $fromsql .= " AND c.id IN ($mycourseids) ";
                } else {
                    return array(array(), 0);
                }
            } else {
                return array(array(), 0);
            }
        }

        try {
            $usercount = $DB->count_records_sql($countsql . $fromsql, $params);
        } catch (dml_exception $e) {
            $usercount = 0;
        }
        try {
            $fromsql .=" GROUP BY u.id ";
            if (!empty($this->sqlorder)) {
                $fromsql .=" ORDER BY ". $this->sqlorder;
            } else {
                if(!empty($sqlorder)){
                    $fromsql .= " ORDER BY u.$sqlorder";
                } else{
                    $fromsql .= " ORDER BY u.id DESC";
                }
            }

            $users = $DB->get_records_sql($selectsql . $fromsql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $users = array();
        }
        return array($users, $usercount);
    }
    /**
     * @param  array $users users
     * @return array $data users courses information
     */
    public function get_rows($users) {
        return $users;
    }

}
