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
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

class report_userquizzes extends reportbase implements report {

    /**
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        parent::__construct($report);
        $this->parent = false;
        $this->columns = array('userfield' => ['userfield'] ,'userquizzes' => array('totalquizs', 'notyetstartedquizs', 'inprogressquizs', 'completedquizs', 'finishedquizs', 'totaltimespent', 'numviews'));
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $this->courselevel = true;
        $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
        $this->filters = array('users');
        $this->orderable = array('fullname', 'notyetstartedquizs', 'inprogressquizs',
            'completedquizs', 'finishedquizs', 'totaltimespent', 'numviews');
    }
    /**
     * [get_all_elements description]
     * @return [type] [description]
     */
    public function get_all_elements($sqlorder = '') {
        global $DB, $USER;

        if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        }
        if(!isset($this->params['filter_courses'])){
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
        $concatsql = '';
        $params = array();
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
            $finishfiltersql = " AND c2.id = $courseid";
            $notyetfiltersql = " AND con.instanceid = $courseid";
            $timespentsql = " AND mt.courseid = $courseid";
            $viewssql = " AND cm.course = $courseid";
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
        if (isset($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $filter_users = $this->params['filter_users'];
            $concatsql .= " AND u.id = $filter_users";
        }
        if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $datefiltersql = " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $params['studentroleid'] = $studentroleid;

        $coursesql  = (new querylib)->get_learners('u.id');

        $quizcountsql = "SELECT COUNT(DISTINCT u.id) ";
        $quizselectsql = "SELECT DISTINCT u.id AS userid, CONCAT(u.firstname, ' ', u.lastname) AS fullname, c.category AS categoryid";
        if (in_array('inprogressquizs', $this->selectedcolumns)) {
            $quizsql .= ", (SELECT COUNT(DISTINCT qat.quiz)
                              FROM {quiz_attempts} qat
                              JOIN {course_modules} cm ON cm.instance = qat.quiz
                                    AND cm.visible = 1 AND cm.deletioninprogress = 0
                              JOIN {course} c ON c.id = cm.course AND c.visible = 1
                              JOIN {modules} m on m.id=cm.module AND m.name = 'quiz'
                             WHERE c.visible = 1 AND qat.state = 'inprogress'
                                    AND qat.userid = u.id $filtersql
                             AND cm.id NOT IN (SELECT cmc1.coursemoduleid
                                                 FROM {course_modules_completion} cmc1
                                                WHERE cmc1.userid = u.id
                                                AND cmc1.completionstate > 0 )
                            $filtersql) AS inprogressquizs";
        }
        if (in_array('finishedquizs', $this->selectedcolumns)) {
            $quizsql .= ", (SELECT COUNT(DISTINCT qat.quiz)
                             FROM {quiz_attempts} qat
                             JOIN {course_modules} cm ON cm.instance = qat.quiz AND cm.visible=1 AND cm.deletioninprogress = 0
                             JOIN {course} c2 ON c2.id = cm.course
                             JOIN {modules} m on m.id = cm.module AND m.name = 'quiz'
                            WHERE c2.visible = 1 AND qat.state = 'finished' AND qat.userid = u.id AND c2.visible = 1 $finishfiltersql) AS finishedquizs";
        }
        if (in_array('completedquizs', $this->selectedcolumns)) {
            $quizsql .= ", (SELECT COUNT(DISTINCT cmc.id)
                             FROM {course_modules} cm
                             JOIN {course} c2 ON c2.id = cm.course
                             JOIN {modules} m on m.id = cm.module AND m.name = 'quiz'
                             JOIN {course_modules_completion} AS cmc ON cmc.coursemoduleid = cm.id
                            WHERE c2.visible = 1 AND cmc.userid = u.id AND cmc.completionstate > 0 AND cm.visible = 1 AND cm.deletioninprogress = 0
                            $finishfiltersql) AS completedquizs";
        }
        if (in_array('notyetstartedquizs', $this->selectedcolumns)) {
            $quizsql .= ", (SELECT COUNT(DISTINCT cm.instance)
                             FROM {course_modules} cm
                             JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz' AND cm.visible = 1 AND cm.deletioninprogress = 0
                            WHERE cm.instance NOT IN (SELECT qat.quiz
                                                        FROM {quiz_attempts} qat
                                                        JOIN {quiz} q ON qat.quiz = q.id
                                                       WHERE qat.userid = u.id)
                              AND cm.id NOT IN (SELECT cmc1.coursemoduleid
                                                  FROM {course_modules_completion} cmc1
                                                 WHERE cmc1.userid = u.id )
                              AND cm.course IN (SELECT co.id
                                                  FROM {course} co
                                                  JOIN {context} con ON co.id = con.instanceid
                                                  JOIN {role_assignments} ras ON ras.contextid = con.id AND ras.roleid = $studentroleid
                                                WHERE ras.userid = u.id AND co.visible = 1
                                                $notyetfiltersql) $viewssql) AS notyetstartedquizs";
        }
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $quizsql .= ", (SELECT SUM(mt.timespent) from {block_ls_modtimestats} as mt JOIN {course_modules} cm ON cm.id = mt.activityid JOIN {modules} m ON m.id = cm.module WHERE m.name = 'quiz' AND mt.userid = u.id $timespentsql) as totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $quizsql .= ", (SELECT COUNT(DISTINCT lsl.userid)  FROM {logstore_standard_log} lsl JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid JOIN {modules} m ON m.id = cm.module WHERE m.name = 'quiz' AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.anonymous = 0 AND lsl.userid = u.id $viewssql) AS distinctusers,
                (SELECT COUNT('X') FROM {logstore_standard_log} lsl
                 JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid
                 JOIN {modules} m ON m.id = cm.module
                WHERE m.name = 'quiz' AND lsl.crud = 'r' AND lsl.contextlevel = 70  AND lsl.anonymous = 0 AND lsl.userid = u.id $viewssql) AS numviews";
        }
        $quizsql .= " FROM {course} AS c
                      JOIN {enrol} AS e ON c.id = e.courseid AND e.status = 0
                      JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.status = 0
                      JOIN {role_assignments} AS ra ON ra.userid = ue.userid
                      JOIN {context} con ON c.id = con.instanceid AND ra.contextid=con.id AND con.contextlevel=50
                      JOIN {role} AS rl ON rl.id = ra.roleid AND rl.shortname = 'student'
                      JOIN {user} AS u ON u.id = ue.userid
                     WHERE c.visible = 1 AND ra.roleid = :roleid AND ra.contextid = con.id
                     AND u.confirmed = 1 AND u.deleted = 0
                     $datefiltersql";
        $quizsql .= $concatsql;
        $params['roleid'] = $studentroleid;
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
        try {

            $quizcount = $DB->count_records_sql($quizcountsql . $quizsql, $params);
        } catch (dml_exception $e) {
            $quizcount = 0;
        }
        try {
            $quizsql .=" GROUP BY u.id";
            if (!empty($this->sqlorder)) {
                $quizsql .=" order by ". $this->sqlorder;
            } else {
                if (!empty($sqlorder)) {
                    $quizsql .= " ORDER BY u.$sqlorder ";
                } else{
                    $quizsql .= " ORDER BY u.id DESC ";
                }
            }
            $quizs = $DB->get_records_sql($quizselectsql . $quizsql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $quizs = array();
        }
        return array($quizs, $quizcount);
    }
    /**
     * [get_rows description]
     * @param  array  $users [description]
     * @return [type]        [description]
     */
    public function get_rows($quizs = array()) {
        return $quizs;
    }
}