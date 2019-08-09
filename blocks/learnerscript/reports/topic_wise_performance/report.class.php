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
 * @subpackage learnerscript
 * @author: Sreekanth<sreekanth@eabyas.in>
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

class report_topic_wise_performance extends reportbase implements report {
    /**
     * [__construct description]
     * @param [type] $report           [description]
     * @param [type] $reportproperties [description]
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        if(is_siteadmin() || $this->role == 'manager') {
            $this->userid = isset($reportproperties->userid) && $reportproperties->userid ? $reportproperties->userid : 0;
        } else {
            $this->userid = isset($reportproperties->userid) && $reportproperties->userid > 0 ? $reportproperties->userid : $this->userid;
        }
        $this->components = array('columns', 'filters', 'permissions', 'plot');

        $this->columns = array("topicwiseperformance" => array("learner", "email"));
        $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
        $this->parent = false;
        $this->courselevel = true;
        $this->filters = array('users');
        $this->orderable = array('learner','email');

    }
    /**
     * [get_all_elements description]
     * @return [type] [description]
     */
    public function get_all_elements() {
        global $DB, $USER;

        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
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
        $this->courseid = isset($this->params['filter_courses']) ? $this->params['filter_courses'] : SITEID ;
        $this->userid = isset($this->params['filter_users']) ? $this->params['filter_users'] : 0;
        if ($this->courseid == SITEID ) {
            return array(array(), 0);
        }
        $userid = $this->userid;

        $elements = array();
        $params = array();
        $concatsql = '';
        $context = context_course::instance($this->courseid);
        list($relatedctxsql, $params) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');
        if (isset($this->search) && $this->search) {
            $fields = array("CONCAT(u.firstname, ' ', u.lastname)", "u.email");
            $fields = implode(" LIKE :search1 OR ", $fields);
            $fields .= " LIKE :search2 ";
            $params['search1'] = '%' . $this->search . '%';
            $params['search2'] = '%' . $this->search . '%';
            $concatsql .= " AND ($fields) ";
        }
        if (isset($userid) && $userid > 0) {
            $concatsql .= " AND u.id = :filter_users";
            $params['filter_users'] = $userid;
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
            $filter_courses = $this->params['filter_courses'];
            $concatsql .= " AND c.id IN ($filter_courses)";
        }
        $datefiltersql = '';
        if($this->ls_startdate >= 0 && $this->ls_enddate) {
            $datefiltersql = " AND ej1_ue.timecreated BETWEEN :ls_startdate AND :ls_enddate ";
            $params['ls_startdate'] = $this->ls_startdate;
            $params['ls_enddate'] = $this->ls_enddate;
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $params['contextlevel'] = CONTEXT_COURSE;
        $params['userid'] = $this->userid;
        $params['ej1_active'] = ENROL_USER_ACTIVE;
        $params['ej1_enabled'] = ENROL_INSTANCE_ENABLED;
        $params['ej1_now1'] = round(time(), -2); // improves db caching
        $params['ej1_now2'] = $params['ej1_now1'];
        $params['ej1_courseid'] = $this->courseid;
        $params['courseid'] = $this->courseid;
        $params['roleid'] = $roleid;
        $topic_wise_performancecountsql = "SELECT COUNT(DISTINCT u.id) ";
        $topic_wise_performancesql = "SELECT DISTINCT u.id, u.picture, u.firstname, u.lastname, CONCAT(u.firstname , u.lastname) as learner, u.email, c.category AS categoryid ";
        $sql = " FROM {user} u
                 LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
                 LEFT JOIN {enrol} e1 ON e1.id = ue.enrolid
                 LEFT JOIN {course} c ON c.id = e1.courseid
                 JOIN (SELECT DISTINCT eu1_u.id, ej1_ue.timecreated
                         FROM {user} eu1_u
                         JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
                         JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = :ej1_courseid)
                        WHERE 1 = 1 AND ej1_ue.status = :ej1_active AND ej1_e.status = :ej1_enabled AND
                        ej1_ue.timestart < :ej1_now1 AND (ej1_ue.timeend = 0 OR ej1_ue.timeend > :ej1_now2) AND
                         eu1_u.deleted = 0 $datefiltersql) e ON e.id = u.id
           LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)
               WHERE u.id IN (SELECT userid
                                FROM {role_assignments}
                               WHERE roleid = :roleid AND contextid $relatedctxsql) $concatsql";
        try {
            $studentscount = $DB->count_records_sql($topic_wise_performancecountsql . $sql, $params);
        } catch (dml_exception $e) {
            $studentscount = 0;
        }
        try {
            if(!empty($this->sqlorder)){
                $sql .= " ORDER BY ". $this->sqlorder;
            } else {
                $sql .= " ORDER BY e.id DESC";
            }
            $students = $DB->get_records_sql($topic_wise_performancesql . $sql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $students = array();
        }
        return array($students, $studentscount);
    }
    /**
     * [get_rows description]
     * @param  [type] $elements [description]
     * @return [type]           [description]
     */
    public function get_rows($elements) {
        global $DB, $CFG, $USER, $OUTPUT;
        $systemcontext = context_system::instance();
        $finalelements = array();
        $this->courseid = isset($this->params['filter_courses']) ? $this->params['filter_courses'] : SITEID ;
        $this->userid = isset($this->params['filter_users']) ? $this->params['filter_users'] : 0;
        $this->categoryid = isset($this->params['filter_coursecategories']) ? $this->params['filter_coursecategories'] : 0;
        if (!empty($elements)) {
            if (!empty($this->params['filter_courses'])) {
                $courseid = $this->params['filter_courses'];
            }
            foreach ($elements as $record) {
                $report = new stdClass();
                $userrecord = $DB->get_record('user',array('id'=>$record->id));
                $reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'userprofile'));
                $userprofilepermissions = empty($reportid) ? false : (new reportbase($reportid))->check_permissions($USER->id, $systemcontext);
                if(empty($reportid) || empty($userprofilepermissions)){
                    $report->learner .= $OUTPUT->user_picture($userrecord, array('size' => 30)) .html_writer::tag('a', fullname($userrecord), array('href' => $CFG->wwwroot.'/user/profile.php?id='.$userrecord->id.''));

                }else{
                    $report->learner = $OUTPUT->user_picture($userrecord, array('size' => 30)) . html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$reportid&filter_users=$record->id&filter_coursecategories=$record->categoryid", ucfirst(fullname($userrecord)), array("target" => "_blank"));
                }
                $report->email = $record->email;
                $sections = $DB->get_records_sql("SELECT * FROM {course_sections} WHERE course = $this->courseid");
                $i = 0;
                foreach($sections as $section){
                    $coursemodulesql = "SELECT sum(gg.finalgrade)/sum(gi.grademax) AS score
                                          FROM {grade_items} AS gi
                                          JOIN {grade_grades} AS gg ON gg.itemid = gi.id
                                          JOIN {course_modules} AS cm ON cm.instance = gi.iteminstance
                                          JOIN {modules} AS m ON m.id = cm.module AND m.name = gi.itemmodule
                                         WHERE cm.section = $section->id AND gg.userid = $record->id";
                    $coursemodulescore = $DB->get_field_sql($coursemodulesql);
                    $sectionkey = "section$i";
                    if($coursemodulescore){
                        $report->$sectionkey = (ROUND($coursemodulescore *100,2)).' %';
                    } else{
                        $report->$sectionkey = '--';
                    }
                    $i++;
                }
                $data[] = $report;
            }
            return $data;
        }
        return $finalelements;
    }
}