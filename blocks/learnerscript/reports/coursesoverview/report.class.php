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
 * LearnerScript
 * A Moodle block for creating customizable reports
 * @package blocks
 * @subpackage learnerscript
 * @author: sreekanth<sreekanth@eabyas.in>
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
defined('MOODLE_INTERNAL') || die();
class report_coursesoverview extends reportbase implements report {
    /**
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        GLOBAL $USER;
        parent::__construct($report);
        $this->components = array('columns', 'conditions', 'filters', 'permissions', 'calcs', 'plot');
        $columns = ['coursename', 'totalactivities', 'completedactivities', 'inprogressactivities', 'grades'];
        $this->columns = ['coursesoverview' => $columns];
        if ($this->role != 'student') {
            $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'users']);
        } else {
            $this->basicparams = array(['name' => 'coursecategories']);
        }
        if ($this->role == 'student') {
            $this->parent = true;
        } else {
            $this->parent = false;
        }
        $this->filters = array('courses', 'modules');
        $this->orderable = array('totalactivities', 'completedactivities', 'inprogressactivities', 'coursename');
        if ($this->role != 'student' && !isset($this->params['filter_users'])) {
            $this->initial_basicparams('users');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_users'] = array_shift($filterdata);
        }
    }
    /**
     * @param  string  $sqlorder user order
     * @param  array  $conditionfinalelements courseids
     * @return array array($courses, $coursescount) list and count of courses
     */
    public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
        global $DB, $USER;
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
        if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        }
        $filtercoursecategories = isset($this->params['filter_coursecategories']) ? $this->params['filter_coursecategories'] : 0;
        $filtercourses = isset($this->params['filter_courses']) ? $this->params['filter_courses'] : SITEID;
        $filtermodules = isset($this->params['filter_modules']) ? $this->params['filter_modules'] : 0;
        $userid = isset($this->params['filter_users']) ? $this->params['filter_users'] : $this->userid;

        $elements = array();
        $params = array();
        $concatsql = '';
        if (isset($this->search) && $this->search) {
            $fields = array('c.fullname');
            $fields = implode(" LIKE :search1 OR ", $fields);
            $fields .= " LIKE :search2 ";
            $params['search1'] = '%' . $this->search . '%';
            $params['search2'] = '%' . $this->search . '%';
            $concatsql .= " AND ($fields) ";
        }
        $filterconcat = '';
        if ($filtercoursecategories > 0) {
            $categoryid = $this->params['filter_coursecategories'];
            $ids = [$categoryid];
            $category = \coursecat::get($categoryid);
            $categoryids = array_merge($ids, $category->get_all_children_ids()); 
        }
        $catids = implode(',', $categoryids);
        if (!empty($catids)) {
             $concatsql .= " AND c.category IN ($catids) ";
             $filterconcat .= " AND c.category IN ($catids) "; 
        }
        if ($filtercourses > SITEID) {
            $filtercourses = $filtercourses;
            $filterconcat .= " AND c.id IN ($filtercourses)";
            $concatsql .= " AND c.id IN ($filtercourses)";
        }
        if ($filtermodules > 0) {
            $filterconcat .= " AND cm.module = $filtermodules";
            $concatsql .= " AND cm.module = :module";
            $params['module'] = $filtermodules;
        }
        if (!empty($conditionfinalelements)) {
            $conditions = implode(',', $conditionfinalelements);
            $concatsql .= " AND c.id IN (:conditions)";
            $params['conditions'] = $conditions;
        }
        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $concatsql .= " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }

        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $coursesql  = (new querylib)->get_learners('', 'cm.course');
        $countsql = "SELECT count(DISTINCT c.id)";
        $selectsql = "SELECT DISTINCT c.id, c.fullname as coursename, c.category AS categoryid";

        if (in_array('coursename', $this->selectedcolumns)) {
            $selectsql .= ", c.fullname as coursename";
        }
        if (in_array('totalactivities', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT count(cm.id) as activitiescount
                              FROM {course_modules} AS cm
                             WHERE cm.course = c.id AND cm.visible =1 $filterconcat
                            ) AS totalactivities";
        }
        if (in_array('completedactivities', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT count(distinct cmc.coursemoduleid)
                               FROM {course_modules_completion} AS cmc
                               JOIN {course_modules} AS cm ON cm.id = cmc.coursemoduleid
                              WHERE cm.course = c.id $filterconcat AND cmc.userid = $userid AND cmc.completionstate > 0 AND cm.visible =1) AS completedactivities";
        }
        if (in_array('inprogressactivities', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT count(distinct cm.id)
                               FROM {course_modules} AS cm
                              WHERE cm.course = c.id $filterconcat
                                AND cm.visible =1
                                AND cm.id NOT IN (SELECT coursemoduleid
                                                    FROM {course_modules_completion}
                                                    WHERE userid = $userid AND completionstate > 0 )
                            ) AS inprogressactivities";
        }
        $fromsql = " FROM {role_assignments} ra
                     JOIN {context} AS ctx ON ctx.id = ra.contextid
                     JOIN {course} c ON c.id = ctx.instanceid
                     JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                     JOIN {course_modules} AS cm ON cm.course = c.id
                    WHERE ra.userid = :userid AND c.visible = 1 AND ra.userid IN ($coursesql)";
        if (empty($this->params['filter_status']) || $this->params['filter_status'] == 'all') {
            $fromsql .= " ";
        }
        if ($this->params['filter_status'] == 'completed') {
            $fromsql .= " AND c.id IN (SELECT course FROM {course_completions} WHERE userid = $userid
                                        AND timecompleted > 0)";
        }
        if ($this->params['filter_status'] == 'inprogress') {
            $fromsql .= " AND c.id NOT IN (SELECT course FROM {course_completions} WHERE userid = $userid
                                            AND timecompleted > 0)";
        }
        $fromsql .= " AND c.visible = 1 $concatsql $filterconcat";
        $params['userid'] = $userid;
        try {
            $coursescount = $DB->count_records_sql($countsql . $fromsql, $params);
        } catch (dml_exception $e) {
            $coursescount = 0;
        }
        $fromsql .= " GROUP by c.id ";
        try {
            if (!empty($this->sqlorder)) {
                $fromsql .= " ORDER BY ". $this->sqlorder;
            } else {
                if (!empty($sqlorder)) {
                    $fromsql .= " ORDER BY c.$sqlorder";
                } else {
                    $fromsql .= " ORDER BY c.id DESC";
                }
            }
            $courses = $DB->get_records_sql($selectsql . $fromsql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $courses = array();
        }
        return array($courses, $coursescount);
    }
    /**
     * @param  array $courses Courses
     * @return array $reportarray courses information
     */
    public function get_rows($courses) {
        global $CFG, $USER, $DB;
        $systemcontext = context_system::instance();
        $filtermodules = isset($this->params['filter_modules']) ? $this->params['filter_modules'] : 0;
        $userid = isset($this->params['filter_users']) ? $this->params['filter_users'] : $this->userid;
        if (!empty($courses)) {
            $activityinfoid = $DB->get_field('block_learnerscript', 'id',
                        array('type' => 'useractivities'));
            $reportpermissions = empty($activityinfoid) ? false : (new reportbase($activityinfoid))->check_permissions(
                                                                    $USER->id, $systemcontext);

            foreach ($courses as $course) {
                $activitiegrade = $this->get_activitiegrade($course->id, $userid, $filtermodules);
                if (!empty($course->totalactivities) || !empty($course->inprogessactivities) ||
                    !empty($course->completedactivities)) {
                    $report = new stdClass();
                    $coursename = $DB->get_field('course', 'fullname', array('id' => $course->id));
                    $reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'courseprofile'));
                    $courseprofilepermissions = empty($reportid) ? false : (new reportbase($reportid))->check_permissions(
                                                                            $USER->id, $systemcontext);
                    if (empty($reportid) || empty($courseprofilepermissions)) {
                        $url = new moodle_url('/course/view.php', array('id' => $course->id));
                    } else {
                        $url = new moodle_url('/blocks/learnerscript/viewreport.php', array('id' => $reportid,'filter_courses' => $course->id, 'filter_coursecategories' => $course->categoryid));
                    }
                    $allactivityurl = new moodle_url('/blocks/learnerscript/viewreport.php', array(
                                                    'id' => $activityinfoid, 'filter_courses' => $course->id,
                                                    'filter_modules' => $filtermodules, 'filter_users' => $userid, 'filter_coursecategories' => $course->categoryid));
                    $inprogressactivityurl = new moodle_url('/blocks/learnerscript/viewreport.php', array(
                                                'id' => $activityinfoid, 'filter_courses' => $course->id,
                                                'filter_status' => 'notcompleted', 'filter_modules' => $filtermodules,
                                                'filter_users' => $userid, 'filter_coursecategories' => $course->categoryid));
                    $completedactivityurl = new moodle_url('/blocks/learnerscript/viewreport.php', array('id' => $activityinfoid,
                                                'filter_courses' => $course->id, 'filter_status' => 'completed',
                                                'filter_modules' => $filtermodules, 'filter_users' => $userid, 'filter_coursecategories' => $course->categoryid));
                    $report->coursename = html_writer::tag('a', $coursename, array('href' => $url));
                    if (empty($activityinfoid) || empty($reportpermissions)) {
                        $report->totalactivities = $course->totalactivities;
                        $report->completedactivities = $course->completedactivities;
                        $report->inprogressactivities = $course->inprogressactivities;
                    } else {
                        $report->totalactivities = html_writer::tag('a', $course->totalactivities,
                            array('href' => $allactivityurl));
                        $report->completedactivities = html_writer::tag('a', $course->completedactivities,
                                                                            array('href' => $completedactivityurl));
                        $report->inprogressactivities = html_writer::tag('a', $course->inprogressactivities,
                                                                            array('href' => $inprogressactivityurl));
                    }
                    $report->grades = $activitiegrade;
                    $reportarray[] = $report;
                }
            }
            return $reportarray;
        }
    }
    /**
     * @param  int $courseid Course ID
     * @param  int $userid User ID
     * @param  int $moduleid Coursemodule ID
     * @return int $grade is course grade
     */
    public function get_activitiegrade($courseid, $userid, $moduleid = '') {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/grade/lib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');
        if (empty($moduleid)) {
            $gradeinstance = grade_get_course_grade($userid, $courseid);
            if ($gradeinstance->grade) {
                $grade = round($gradeinstance->grade, 2) . '/' . round($gradeinstance->item->grademax, 2);
            } else {
                $grade = '--';
            }
        } else {
            $modulename = $DB->get_field('modules', 'name', array('id' => $moduleid));
            try {
                $gradesql = "SELECT  sum(gg.finalgrade) AS finalgrade, sum(gi.grademax) AS grademax
                               FROM {grade_grades} gg
                               JOIN {grade_items} gi ON gi.id = gg.itemid
                              WHERE gg.userid = $userid AND gi.courseid = $courseid
                                AND gi.itemmodule = '$modulename'";
                $activitygrade = $DB->get_record_sql("$gradesql");
            } catch (dml_exception $e) {
                print_error('countsqlwrong', 'block_learnerscript');
            }
            if ($activitygrade->finalgrade) {
                $grade = round($activitygrade->finalgrade, 2) . '/' . round($activitygrade->grademax, 2);
            } else {
                $grade = '--';
            }
        }
        return $grade;
    }
}