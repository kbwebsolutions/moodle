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
 * A Moodle block for creating cccstomizable reports
 * @package blocks
 * @author: Arun Kumar M
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\reportbase;
use block_learnerscript\local\querylib;
use block_learnerscript\report;
defined('MOODLE_INTERNAL') || die();

class report_competencycompletion extends reportbase implements report {
    /**
     * @param [type]
     * @param [type]
     */
    public function __construct($report, $reportproperties) {
        parent::__construct($report);
        $this->components = array('columns', 'filters', 'permissions', 'calcs', 'plot');
        $this->courselevel = false;
        $columns = ['competencyname' => 'competencyname',
                              'rating' => 'rating',
                     'completiondate' => 'completiondate',
                             'status' => 'status',
                               'name' => 'name',
                             'course' => 'course'
                   ];
        $this->columns = ['competencycompletion' => $columns];
        $this->parent = true;
        $this->orderable = array('competencyname', 'rating', 'completiondate');
        $this->basicparams = array(['name' => 'coursecategories']);
        $this->filters = array('courses', 'users');
    }
    public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
        global $DB, $USER;
        $params = array();
        $concatsql = '';
        $datefiltersql = '';
        $elements = array();
        if (isset($this->search) && $this->search) {
            $fields = array('cc.shortname', 'CONCAT(u.firstname, " " , u.lastname)', 'c.fullname');
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $concatsql .= " AND ($fields) ";
        }
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
        if (!empty($this->params['filter_courses'])) {
            $concatsql .= " AND ccc.courseid  = :courseid";
            $params['courseid'] = $this->params['filter_courses'];
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
            $concatsql .= " AND u.id = :filter_users";
            $params['filter_users'] = $this->params['filter_users'];
        }
        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $datefiltersql .= " AND c.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $concatsql .= " AND ccc.timemodified BETWEEN :ls_startdate AND :ls_enddate ";
            $params['ls_startdate'] = $this->ls_startdate;
            $params['ls_enddate'] = $this->ls_enddate;
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $competencycompletioncountsql = " SELECT COUNT(distinct ccc.id) ";
        $competencycompletionsql = "SELECT DISTINCT ccc.id, 
                                    ccc.userid, u.firstname, u.lastname, 
                                     cc.competencyframeworkid, ccc.competencyid, cf.scaleid AS scaleid, c.category AS categoryid ";
        if (in_array('competencyname', $this->selectedcolumns)) {
            $competencycompletionsql .= ", cc.shortname AS competencyname";
        }
        if (in_array('rating', $this->selectedcolumns)) {
            $competencycompletionsql .= ", ccc.grade AS rating";
        }
        if (in_array('completiondate', $this->selectedcolumns)) {
            $competencycompletionsql .= ", ccc.timemodified AS completiondate";
        }
        if (in_array('status', $this->selectedcolumns)) {
            $competencycompletionsql .= ", ccc.proficiency AS status";
        }
        $sql .= " FROM {competency} AS cc
                JOIN {competency_usercompcourse} AS ccc ON ccc.competencyid = cc.id
                JOIN {competency_framework} AS cf ON  cf.id = cc.competencyframeworkid
                JOIN {user} AS u ON u.id = ccc.userid
                JOIN {course} c ON c.id = ccc.courseid
                WHERE ccc.id > 0 AND cf.visible = 1 AND ccc.proficiency IS NOT NULL
                AND u.deleted = 0 AND u.confirmed = 1 AND u.suspended = 0 $concatsql";

        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            if (!empty($this->role)) {
                $roleshortname = $this->role;
                $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
                if (!empty($mycourses)) {
                    $mycourseids = implode(',', array_keys($mycourses));
                    $sql .= " AND ccc.courseid IN ($mycourseids) ";
                } else {
                    return array(array(), 0);
                }
            } else {
                return array(array(), 0);
            }
        }
        try {
            $totalrecords = $DB->count_records_sql($competencycompletioncountsql . $sql, $params);
        } catch (dml_exception $e) {
            $totalrecords = 0;
        }

        try {
            if (!empty($this->sqlorder)) {
                $sql .= " ORDER BY " . $this->sqlorder;
            } else {
                $sql .= " ORDER BY ccc.id DESC";
            }
            $records = $DB->get_records_sql($competencycompletionsql . $sql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $records = array();
        }
        return array($records, $totalrecords);
    }
    public function get_rows($elements = array()) {
        global $CFG, $DB;
        $systemcontext = context_system::instance();
        $reportarray = array();
        if (!empty($elements)) {
            foreach ($elements as $rec) {
                $courserecord = $DB->get_record_sql("SELECT c.id, c.fullname,  c.category  FROM {course} AS c
                                                       JOIN {competency_usercompcourse} AS cc ON cc.courseid = c.id
                                                       WHERE  cc.competencyid = $rec->competencyid AND cc.userid =
                                                       $rec->userid");
                $manger = new stdClass();
                $competencyname = html_writer::tag('a', $rec->competencyname, array('href' => $CFG->wwwroot.'/admin/tool/lp/competencies.php?competencyframeworkid='.$rec->competencyframeworkid.'&pagecontextid=1'));
                $manger->competencyname = $competencyname;
                $userreport = $DB->get_field('block_learnerscript', 'id', array('type' => 'userprofile'));
                $userprofilepermissions = empty($userreport) ? false : (new reportbase($userreport))->check_permissions(
                                                                        $USER->id, $systemcontext);
                if (empty($userreport) || empty($userprofilepermissions)) {
                    $userfullname = html_writer::tag('a', $rec->firstname.' '.$rec->lastname,
                                    array('href' => $CFG->wwwroot.'/user/profile.php?id='.$rec->userid ));
                } else {
                    $userfullname = html_writer::tag('a', $rec->firstname.' '.$rec->lastname,
                                    array('href' => $CFG->wwwroot.'/blocks/learnerscript/viewreport.php?id='.
                                    $userreport.'&filter_users='.$rec->userid ));
                }
                $manger->name = $userfullname;
                $reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'courseprofile'));
                $courseprofilepermissions = empty($reportid) ? false : (new reportbase($reportid))->check_permissions(
                                                                        $USER->id, $systemcontext);
                if (empty($reportid) || empty($courseprofilepermissions)) {
                    $coursefullname = html_writer::tag('a', $courserecord->fullname, array('href' => $CFG->wwwroot.'/course/view.php?id='.$courserecord->id ));
                } else {
                    $coursefullname = html_writer::tag('a', $courserecord->fullname, array('href' => $CFG->wwwroot.'/blocks/learnerscript/viewreport.php?id='.$reportid.'&filter_courses='.$courserecord->id.'&filter_coursecategories='.$courserecord->category ));
                }

                $manger->course = $coursefullname;
                $rec->status = intval($rec->status);
                if ($rec->status == 1) {
                    $manger->status = 'Completed';
                } else if ($rec->status == 0) {
                    $manger->status = 'Not Completed';
                }
                if (!empty($rec->completiondate) && $rec->status == 1) {
                    $manger->completiondate = userdate($rec->completiondate);
                } else {
                    $manger->completiondate = '--';
                }
                $scalename = $DB->get_field_sql("SELECT scale FROM {scale} WHERE id = $rec->scaleid");
                $scalename = explode(',', $scalename);
                if (!empty($scalename[$rec->rating - 1])) {
                    $manger->rating = ucfirst(trim($scalename[$rec->rating - 1]));
                } else {
                    $manger->rating = '--';
                }
                $reportarray[] = $manger;
            }
        }
        return $reportarray;
    }
}