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

class report_quizzes extends reportbase implements report {
    /**
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        parent::__construct($report);
        $this->parent = true;
        $this->columns = array('quizfield' => ['quizfield'] , 'quizzes' => array(
            'avggrade', 'grademax', 'gradepass', 'notattemptedusers', 'inprogressusers',
            'completedusers', 'noofcompletegradedfirstattempts',
            'totalnoofcompletegradedattempts', 'avggradeoffirstattempts',
            'avggradeofallattempts', 'avggradeofhighestgradedattempts', 'totaltimespent', 'numviews'));
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $this->courselevel = false;
        $this->basicparams = array(['name' => 'coursecategories']);
        $this->filters = array('courses');
        $this->orderable = array('avggrade', 'grademax', 'gradepass', 'notattemptedusers', 'inprogressusers','completedusers', 'noofcompletegradedfirstattempts',
            'totalnoofcompletegradedattempts', 'avggradeoffirstattempts',
            'avggradeofallattempts', 'avggradeofhighestgradedattempts', 'totaltimespent', 'numviews','name','course');

    }
    /**
     * [get_all_elements description]
     * @return [type] [description]
     */
    public function get_all_elements() {
        global $DB, $USER;
        $elements = array();
        $concatsql = '';
        $params = array();
        
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
            $fields = array('q.name', 'c.fullname', 'c.shortname');
            $fields = implode(" LIKE '%$this->search%' OR ", $fields);
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
            $courseid = $this->params['filter_courses'];
            $concatsql .= " AND c.id IN ($courseid)";
            $timespentsql = " AND bt.courseid IN ($courseid)";
        }
        if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $datefiltersql = " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }

        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $params['studentroleid'] = $studentroleid;
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $quizcountsql = "SELECT COUNT(DISTINCT q.id) ";
        $quizselectsql = "SELECT DISTINCT q.id, q.name as name, cm.id as activityid, m.id as module, c.fullname as course, q.course as courseid,csc.name as section, c.category AS categoryid";
        $quizsql = '';
        $coursesql  = (new querylib)->get_learners('','q.course');
        if(in_array('grademax', $this->selectedcolumns)){
            $quizsql .= ", ROUND(q.grade, 2) AS grademax";
        }
        if(in_array('gradepass', $this->selectedcolumns)){
            $quizsql .= ", (SELECT ROUND(gi.gradepass,2) FROM {grade_items} as gi
                             WHERE gi.itemtype='mod' AND gi.itemmodule = 'quiz'
                               AND gi.iteminstance = q.id) AS gradepass";
        }
        if(in_array('avggrade', $this->selectedcolumns)){
            $quizsql .= " , (SELECT ROUND(AVG(gg.rawgrade),2)
                            FROM {grade_items} AS gi
                            JOIN {grade_grades} AS gg ON gi.id = gg.itemid
                            WHERE gi.itemmodule = 'quiz' AND gi.iteminstance = q.id AND gg.userid IN ($coursesql)) as avggrade";
        }
        if(in_array('noofcompletegradedfirstattempts', $this->selectedcolumns)){
            $quizsql .= ", (SELECT COUNT(*) AS rcount
                            FROM {quiz_attempts} quiza
                            WHERE quiza.quiz = q.id AND quiza.preview = 0 AND quiza.state = 'finished' AND (quiza.state = 'finished' AND NOT EXISTS ( SELECT 1 FROM {quiz_attempts} qa2 WHERE qa2.quiz = quiza.quiz AND qa2.userid = quiza.userid AND qa2.state = 'finished' AND qa2.attempt < quiza.attempt)) AND quiza.sumgrades IS NOT NULL AND quiza.userid IN ($coursesql)) as noofcompletegradedfirstattempts";
        }
        if(in_array('totalnoofcompletegradedattempts', $this->selectedcolumns)){
            $quizsql .= ", (SELECT COUNT(*)
                              FROM {quiz_attempts} quiza WHERE quiza.quiz = q.id AND quiza.preview = 0 AND quiza.state = 'finished' AND quiza.sumgrades IS NOT NULL AND quiza.userid IN ($coursesql)) as totalnoofcompletegradedattempts";
        }
        if(in_array('avggradeofhighestgradedattempts', $this->selectedcolumns)){
            $quizsql .= ", (SELECT CONCAT(IF(q.sumgrades > 0, ROUND(IF(AVG(sumgrades) > 0, AVG(sumgrades) * 100 / q.sumgrades, 0), 2), 0), '%')
                              FROM {quiz_attempts} quiza
                              WHERE quiza.quiz = q.id AND quiza.preview = 0
                              AND quiza.state = 'finished' AND
                              (quiza.state = 'finished' AND NOT EXISTS ( SELECT 1
                                            FROM {quiz_attempts} qa2
                                            WHERE qa2.quiz = quiza.quiz AND qa2.userid = quiza.userid AND qa2.state = 'finished'
                                            AND ( COALESCE(qa2.sumgrades, 0) > COALESCE(quiza.sumgrades, 0) OR (COALESCE(qa2.sumgrades, 0) = COALESCE(quiza.sumgrades, 0) AND qa2.attempt < quiza.attempt) ))) AND quiza.sumgrades IS NOT NULL AND quiza.userid IN ($coursesql)) as avggradeofhighestgradedattempts";
        }
        if(in_array('avggradeoffirstattempts', $this->selectedcolumns)){
            $quizsql .= ", (SELECT CONCAT(IF(q.sumgrades > 0, ROUND(IF(AVG(sumgrades) > 0, AVG(sumgrades) * 100 / q.sumgrades, 0), 2), 0), '%')
                              FROM {quiz_attempts} quiza WHERE quiza.quiz = q.id
                              AND quiza.preview = 0 AND quiza.state = 'finished' AND (quiza.state = 'finished' AND NOT EXISTS ( SELECT 1 FROM {quiz_attempts} qa2 WHERE qa2.quiz = quiza.quiz AND qa2.userid = quiza.userid AND qa2.state = 'finished' AND qa2.attempt < quiza.attempt)) AND quiza.sumgrades IS NOT NULL AND quiza.userid IN ($coursesql)) as avggradeoffirstattempts";
        }
        if(in_array('avggradeofallattempts', $this->selectedcolumns)){
            $quizsql .= ", (SELECT CONCAT(IF(q.sumgrades > 0, ROUND(IF(AVG(sumgrades) > 0, AVG(sumgrades) * 100 / q.sumgrades, 0), 2), 0), '%')
                              FROM {quiz_attempts} quiza WHERE quiza.quiz = q.id AND quiza.preview = 0 AND quiza.state = 'finished' AND quiza.sumgrades IS NOT NULL AND quiza.userid IN ($coursesql)) as avggradeofallattempts";
        }
        if(in_array('inprogressusers', $this->selectedcolumns)){
            $quizsql .= ", (SELECT COUNT(DISTINCT qat.userid)
                              FROM {quiz_attempts} qat
                              JOIN {user} u ON qat.userid = u.id
                             WHERE qat.state = 'inprogress' AND qat.quiz = q.id AND u.deleted = 0 AND u.confirmed = 1 AND u.suspended = 0 AND qat.userid IN ($coursesql)) AS inprogressusers";
        }
        if(in_array('completedusers', $this->selectedcolumns)){
           $quizsql .=", (SELECT COUNT(DISTINCT cmc.id)
                           FROM {course_modules_completion} cmc
                           WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND cmc.coursemoduleid = cm.id AND m.name = 'quiz' AND cm.instance = q.id AND cmc.userid > 2 AND cmc.completionstate != 0 AND cmc.userid IN ($coursesql)) AS completedusers";
        }
        if(in_array('notattemptedusers', $this->selectedcolumns)){
            $quizsql .=", (SELECT COUNT(DISTINCT u.id)
                            FROM {user} u
                            WHERE u.id NOT IN (SELECT qat.userid
                                                 FROM {quiz_attempts} qat
                                                 JOIN {user} u ON qat.userid = u.id
                                                WHERE qat.quiz = q.id AND qat.userid IN ($coursesql)) AND c.id IN (SELECT co.id FROM {course} co
                                                        JOIN {context} con ON co.id = con.instanceid
                                                        JOIN {role_assignments} ras ON ras.contextid = con.id
                                                        WHERE ras.userid = u.id AND ras.roleid = $studentroleid
                                                        AND co.visible = 1 AND u.deleted = 0 AND u.confirmed = 1 AND u.suspended = 0 AND ras.userid IN ($coursesql)) AND u.deleted = 0 AND u.confirmed = 1 AND u.suspended = 0)  AS notattemptedusers";
        }
        if(in_array('totaltimespent', $this->selectedcolumns)){
            $quizsql .= ", (SELECT SUM(mt.timespent) FROM {block_ls_modtimestats} AS mt WHERE cm.id = mt.activityid AND mt.courseid = c.id) AS totaltimespent";
        }
        if(in_array('numviews', $this->selectedcolumns)){
            $quizsql .= ", (SELECT COUNT(DISTINCT lsl.userid)  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND u.confirmed = 1 AND u.deleted = 0) AS distinctusers,
               (SELECT COUNT('X')  FROM {logstore_standard_log} lsl JOIN {user} u ON u.id = lsl.userid WHERE lsl.contextinstanceid = cm.id AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.userid > 2 AND lsl.courseid = cm.course AND lsl.anonymous = 0 AND u.confirmed = 1 AND u.deleted = 0) AS numviews";
        }
        $quizsql .=" FROM {quiz} q
                    JOIN {course_modules} as cm ON cm.instance = q.id
                    JOIN {modules} m ON cm.module = m.id
                    JOIN {course} c ON c.id = cm.course
                    JOIN {course_sections} as csc ON csc.id = cm.section
                    WHERE m.name = 'quiz' AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1 $datefiltersql
                     ";

        $quizsql .= $concatsql;

        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            if(!empty($this->role)){
                $roleshortname = $this->role;
                $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
                if (!empty($mycourses)) {
                    $mycourseids = implode(',', array_keys($mycourses));
                    $quizsql .= " AND c.id IN ($mycourseids) ";
                }else{
                    return array(array(), 0);
                }
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
            $quizsql .=" GROUP BY q.id";
            if(!empty($this->sqlorder)){
              $quizsql .= " ORDER BY ". $this->sqlorder;
            } else{
				      $quizsql .=" order by q.id desc";
			       }
            $quizs = $DB->get_records_sql($quizselectsql . $quizsql , $params, $this->start, $this->length);
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