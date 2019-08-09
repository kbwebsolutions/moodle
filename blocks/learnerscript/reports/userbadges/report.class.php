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
 * @author: manikanta
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/badgeslib.php');
use block_learnerscript\local\reportbase;
use block_learnerscript\report;

class report_userbadges extends reportbase implements report {

    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        // if ($this->role == 'student') {
        //     $this->parent = true;
        // } else {
        //     $this->parent = false;
        // }
        if ($this->role != 'student') {
            $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'users']);
        } else {
           $this->basicparams = array(['name' => 'coursecategories']);
        }
        $this->columns = array('userbadges' => array('name','issuername','coursename','timecreated','dateissued', 'description', 'criteria','expiredate'));
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $this->filters = array('courses');
        if (isset($this->role) && $this->role == 'student') {
            $this->parent = true;
        } else {
            $this->parent = false;
        }
        $this->orderable = array('name', 'issuername', 'timecreated', 'dateissued', 'description', 'expiredate');

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
        $searchconcat = '';
        if (isset($this->search) && $this->search) {
            $fields = array('b.name','c.fullname');
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $searchconcat = " AND ($fields) ";
        }
        if (!empty($this->params['filter_courses'])) {
            // $courseids = $this->params['filter_courses'];
            $searchconcat .= " AND b.courseid = :courseid";
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
             $searchconcat .= " AND c.category IN ($catids) ";
        }
        if (isset($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $userid = $this->params['filter_users'];
            $searchconcat .= " AND bi.userid = $userid";
        }
        if($this->ls_startdate >= 0 && $this->ls_enddate) {
            $searchconcat .= " AND bi.dateissued BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        $mycourses = (new block_learnerscript\local\querylib)->get_rolecourses($userid, 'student', SITEID, '', '', '', false, false);
        $mycourseids = implode(',', array_keys($mycourses));
        if(empty($mycourseids)){
            return array(array(), 0);
        }

        $badgecountsql  = "SELECT COUNT(bi.id) ";
        $badgeselectsql = "SELECT bi.id, b.courseid, bi.userid, b.id as badgeid, c.fullname,  c.category AS categoryid ";
        if (in_array('name', $this->selectedcolumns)) {
            $badgeselectsql .= ", b.name AS name";
        }
        if (in_array('issuername', $this->selectedcolumns)) {
            $badgeselectsql .= ", b.issuername AS issuername";
        }
        if (in_array('timecreated', $this->selectedcolumns)) {
            $badgeselectsql .= ", b.timecreated AS timecreated";
        }
        if (in_array('dateissued', $this->selectedcolumns)) {
            $badgeselectsql .= ", bi.dateissued AS dateissued";
        }
        if (in_array('description', $this->selectedcolumns)) {
            $badgeselectsql .= ", b.description AS description";
        }
        if (in_array('expiredate', $this->selectedcolumns)) {
            $badgeselectsql .= ", b.expiredate AS expiredate";
        }
        $badgesql = " FROM {badge_issued} as bi
                        JOIN {badge} as b ON b.id = bi.badgeid
                        LEFT JOIN {course} as c ON b.courseid = c.id AND c.visible = 1 AND b.courseid IN ($mycourseids)
                        WHERE  bi.visible = 1 AND b.status != 0 AND b.status != 2 AND b.status != 4
                        AND bi.userid = $userid $searchconcat
                        GROUP BY bi.id";
        try{
            $badgecounts = $DB->get_records_sql($badgeselectsql . $badgesql, $params);
            $badgecount = COUNT($badgecounts);
        } catch (dml_exception $e){
            $badgecount = 0;
        }
        try{
            if(!empty($this->sqlorder)){
                $badgesql .=" ORDER BY ". $this->sqlorder;
            } else{
                $badgesql .=" ORDER BY bi.id desc";
            }
            $badges = $DB->get_records_sql($badgeselectsql.$badgesql, $params, $this->start, $this->length);
        } catch (dml_exception $e){
            $badges = array();
        }
        return array($badges, $badgecount);
    }

    public function get_rows($badges) {
        global $DB, $CFG, $PAGE, $OUTPUT, $USER;
        $context = context_system::instance();
        $userid = isset($this->params['filter_users']) && $this->params['filter_users'] > 0
                    ? $this->params['filter_users'] : $this->userid;
        $data = array();
        if (!empty($badges)) {
            foreach ($badges as $badge) {
                $batchinstance = new badge($badge->badgeid);
                $context = $batchinstance->get_context();
                $badgeimage = print_badge_image($batchinstance, $context);
                $get_criteria = $PAGE->get_renderer('core_badges');
                $criteria = $get_criteria->print_badge_criteria($batchinstance);
                $courserecord = $DB->get_record('course',array('id'=>$badge->courseid));
                $completion_info = new completion_info($courserecord);
                $activityinforeport = new stdClass();
                $params = array();
                $params['userid'] = $userid;
                if ($this->ls_startdate >= 0 && $this->ls_enddate) {
                    $datefiltersql = " AND gg.timemodified BETWEEN :startdate AND :enddate ";
                    $datesql = " AND timemodified BETWEEN :startdate AND :enddate ";
                    $params['startdate'] = $this->ls_startdate;
                    $params['enddate'] = $this->ls_enddate;
                }
                $activityinforeport->name = '<a href="'. $CFG->wwwroot. '/badges/overview.php?id='.$badge->badgeid.'" target="_blank" class="edit">'.$badgeimage.'  '.$badge->name.'</a>';
                $activityinforeport->username = $userrecord->firstname.'  '.$userrecord->lastname;
                $activityinforeport->issuername = $badge->issuername;
                if($badge->courseid = NULL || empty($badge->courseid)){
                    $activityinforeport->coursename = $courserecord->fullname ? $courserecord->fullname : 'System';
                }else{
                    $reportid = $DB->get_field('block_learnerscript', 'id', array('type'=> 'courseprofile'));
                    $permissions = empty($reportid) ? false : (new reportbase($reportid))->check_permissions($USER->id, $context);
                    if(empty($reportid) || empty($permissions)){
                        $activityinforeport->coursename = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courserecord->id.'" target="_blank" class="edit">'.($courserecord->fullname ? $courserecord->fullname : 'System').'</a>';
                    }else{
                        $activityinforeport->coursename = '<a href="'.$CFG->wwwroot.'/blocks/learnerscript/viewreport.php?id='.$reportid.'&filter_courses='.$courserecord->id.'&filter_coursecategories='.$courserecord->category.'" target="_blank" class="edit">'.($courserecord->fullname ? $courserecord->fullname : 'System').'</a>';
                    }
                }
                $activityinforeport->timecreated = date('l, d F Y H:i A',$badge->timecreated);
                $activityinforeport->dateissued = date('l, d F Y H:i A',$badge->dateissued);
                if(!empty($badge->expiredate)){
                $activityinforeport->expiredate = date('l, d F Y H:i A',$badge->expiredate);
                }else{
                $activityinforeport->expiredate = "--";
                }
                $activityinforeport->description =$badge->description;
                $activityinforeport->criteria =$criteria;
                $activityinforeport->recipients =$badge->recipients;
                $activityinforeport->recipients = '<a href="'. $CFG->wwwroot. '/badges/recipients.php?id='.$badge->badgeid.'" target="_blank" class="edit">'.$recipients.'</a>';
                $data[] = $activityinforeport;
            }
        }
        return $data;
    }
}