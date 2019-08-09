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
require_once($CFG->libdir . '/completionlib.php');
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

class report_myquizs extends reportbase implements report {

    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        $this->columns = array('quizfield' => ['quizfield'], 'myquizs' => array('attempts', 'status', 'state','grademax', 'grademin', 'finalgrade','gradepass', 'highestgrade', 'lowestgrade'));
        if(isset($this->role) && $this->role == 'student') {
            $this->parent = true;
        } else {
            $this->parent = false;
        }
        if ($this->role != 'student' || is_siteadmin()) {
            $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'users']);
        } else {
            $this->basicparams = array(['name' => 'coursecategories']);
        }
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $this->courselevel = false;
        $this->filters = array('courses');
        $this->orderable = array('attempts', 'grademax', 'finalgrade','gradepass', 'highestgrade', 'lowestgrade','name','course');

    }
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
        $params = array();
        $elements = array();
        $searchconcat = '';
        if (isset($this->search) && $this->search) {
            $fields = array('q.name','c.fullname','q.grade','m.name','gr.name');
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
        if (!empty($this->params['filter_courses']) && $this->params['filter_courses'] > 1) {
            $searchconcat .= " AND cm.course =:courseid";
            $params['courseid'] = $this->params['filter_courses'];
        }
 	      if (isset($this->params['filter_users']) && $this->params['filter_users'] > 0) {
          $filter_users = $this->params['filter_users'];
          $searchconcat .= " AND ra.userid IN ($filter_users)";
        }
        $inprogressdurationfilter = '';
        $comquizdatefiltersql = '';
        $alldurationfilter = '';
        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $searchconcat .= " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $params['startdate'] = $this->ls_startdate;
            $params['enddate'] = $this->ls_enddate;
        }

        $mycourses = (new block_learnerscript\local\querylib)->get_rolecourses($userid, 'student', SITEID, '', '', '', false, false);
        $mycourseids = implode(',', array_keys($mycourses));

        if(empty($mycourseids)){
            return array(array(), 0);
        }
        $coursesql  = (new querylib)->get_learners('','q.course');
        $quizcountsql  = "SELECT COUNT(DISTINCT q.id) ";
        $quizselectsql = "SELECT DISTINCT q.id, q.name as name, cm.course as courseid, c.fullname as course, m.name as modname, cm.id AS activityid , c.category AS categoryid,
            (SELECT gi.gradetype FROM {grade_items} gi WHERE gi.iteminstance = q.id AND gi.itemmodule = 'quiz') AS gradetype ";
        if(in_array('grademax', $this->selectedcolumns)){
            $quizselectsql .= ", ROUND(q.grade, 2) AS grademax";
        }
        if(in_array('grademin', $this->selectedcolumns)){
            $quizselectsql .= ", ROUND(gi.grademin, 2) AS grademin";
        }
        if(in_array('finalgrade', $this->selectedcolumns)){
            $quizselectsql .= ", ROUND(gg.finalgrade, 2) as finalgrade";
        }
        if(in_array('gradepass', $this->selectedcolumns)){
            $quizselectsql .= ", ROUND(gi.gradepass, 2) as gradepass";
        }
        if(in_array('lowestgrade', $this->selectedcolumns)){
            $quizselectsql .= ", (SELECT ROUND(MIN(gg.finalgrade), 2) 
                        FROM {grade_grades} gg JOIN {grade_items} gi ON gi.id = gg.itemid
                        WHERE gi.itemmodule = 'quiz' AND gi.iteminstance = q.id) AS lowestgrade";
        }
        if(in_array('attempts', $this->selectedcolumns)){
            $quizselectsql .= ", (SELECT count(id) FROM {quiz_attempts}
                                      WHERE userid = $userid AND quiz=q.id) AS attempts ";
        }
        if(in_array('highestgrade', $this->selectedcolumns)){
            $quizselectsql .= ", (SELECT ROUND(MAX(gg.finalgrade), 2) FROM {grade_grades} gg
                                 JOIN {grade_items} gi ON gi.id = gg.itemid
                                WHERE gi.itemmodule = 'quiz' AND gi.iteminstance = q.id) AS highestgrade ";
        }
        if(empty($this->params['filter_status']) || $this->params['filter_status'] == 'all') {
            $quizsql       = " FROM {modules} as m
                               JOIN {course_modules} as cm ON cm.module = m.id
                               JOIN {quiz} as q ON q.id = cm.instance
                          LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id AND qa.userid = $userid
                               LEFT JOIN {groupings} gr ON gr.id = cm.groupingid
                               JOIN {course} as c ON c.id = cm.course
                               JOIN {context} AS ctx ON c.id = ctx.instanceid
                               JOIN {role_assignments} as ra ON ctx.id = ra.contextid AND ra.userid = $userid
                          LEFT JOIN {grade_items} gi ON gi.itemmodule = 'quiz' AND gi.courseid = c.id AND gi.iteminstance = cm.instance
                          LEFT JOIN {grade_grades} gg ON gg.itemid =gi.id AND gg.userid = $userid
                              WHERE c.visible = 1 AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.id IN ($mycourseids) AND m.name = 'quiz'";
            $quizsql      .= "  AND m.visible = 1 $searchconcat $alldurationfilter";
        } else if($this->params['filter_status'] == 'inprogress') {
            $quizsql      = "   FROM {quiz} q
                                JOIN {quiz_attempts} qa ON qa.quiz = q.id
                                JOIN {course_modules} as cm ON cm.instance = q.id
                                JOIN {modules} m ON m.id = cm.module
                                JOIN {course} as c ON c.id = cm.course
                                JOIN {enrol} AS e ON c.id = e.courseid AND e.status = 0
                                JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.status = 0
                                JOIN {context} AS ctx ON c.id = ctx.instanceid
                                JOIN {role_assignments} as ra ON ctx.id = ra.contextid AND ra.userid = $userid
                           LEFT JOIN {groupings} gr ON gr.id = cm.groupingid
                           LEFT JOIN {grade_items} gi ON gi.itemmodule = 'quiz' AND gi.courseid = c.id AND gi.iteminstance = cm.instance
                           LEFT JOIN {grade_grades} gg ON gg.itemid =gi.id AND gg.userid = $userid
                               WHERE qa.userid = :userid AND qa.state = 'inprogress' AND m.name='quiz'
                                 AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1
                                 AND cm.id NOT IN (SELECT coursemoduleid FROM  {course_modules_completion} cmc
                                                 WHERE cmc.userid = $userid AND cmc.completionstate > 0)
                             $inprogressdurationfilter $searchconcat";
            $params['userid'] = $userid;
        } else if($this->params['filter_status'] == 'completed') {
            $quizsql      = "   FROM {quiz} q
                                JOIN {course_modules} as cm ON cm.instance = q.id
                                JOIN {modules} m ON m.id = cm.module
                                JOIN {course} as c ON c.id = cm.course
                                JOIN {context} AS ctx ON c.id = ctx.instanceid
                                JOIN {role_assignments} as ra ON ctx.id = ra.contextid AND ra.userid = $userid
                           LEFT JOIN {groupings} gr ON gr.id = cm.groupingid
                           LEFT JOIN {grade_items} gi ON gi.itemmodule = 'quiz' AND gi.courseid = c.id AND gi.iteminstance = cm.instance
                           LEFT JOIN {grade_grades} gg ON gg.itemid =gi.id AND gg.userid = $userid
                                JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id
                               WHERE cmc.userid = :userid AND m.name='quiz'
                                AND cm.visible = 1 AND cm.deletioninprogress = 0 AND cmc.completionstate > 0 AND c.visible = 1
                                AND ra.userid IN ($coursesql) $comquizdatefiltersql $searchconcat";
            $params['userid'] = $userid;
        } else if($this->params['filter_status'] == 'notyetstarted') {
            $quizsql      = " FROM {quiz} q
                              JOIN {course} c ON c.id = q.course
                              JOIN {course_modules} cm ON cm.instance = q.id
                              JOIN {modules} m ON m.id = cm.module
                              JOIN {context} AS ctx ON c.id = ctx.instanceid
                              JOIN {role_assignments} as ra ON ctx.id = ra.contextid AND ra.userid = $userid
                         LEFT JOIN {groupings} gr ON gr.id = cm.groupingid
                         LEFT JOIN {grade_items} gi ON gi.itemmodule = 'quiz' AND gi.courseid = c.id AND gi.iteminstance = cm.instance
                         LEFT JOIN {grade_grades} gg ON gg.itemid =gi.id AND gg.userid = $userid
                             WHERE c.id IN ($mycourseids) AND cm.visible = 1
                               AND c.visible = 1 AND cm.deletioninprogress = 0 AND m.name = 'quiz' $searchconcat
                               AND q.id NOT IN (SELECT qa.quiz FROM  {quiz_attempts} qa
                                                 WHERE qa.userid = $userid AND qa.userid IN ($coursesql) $alldurationfilter)
                               AND cm.id NOT IN (SELECT coursemoduleid FROM  {course_modules_completion} cmc
                                                 WHERE cmc.userid = $userid AND cmc.userid IN ($coursesql))
                              ";
            $params['userid'] = $userid;
        }
        try{
            $quizcount = $DB->count_records_sql($quizcountsql . $quizsql, $params);
        } catch (dml_exception $e){

            $quizcount = 0;
        }
        try{
            if(!empty($this->sqlorder)){
                $quizsql .=" order by ". $this->sqlorder;
            } else{
                $quizsql .= " ORDER BY q.id DESC";
            }
            $quizs = $DB->get_records_sql($quizselectsql . $quizsql, $params, $this->start, $this->length);
        } catch (dml_exception $e){
            $quizs = array();
        }
        return array($quizs, $quizcount);
    }

    public function get_rows($quizs) {
        return $quizs;
    }
}