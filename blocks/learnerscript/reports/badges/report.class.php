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
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/badgeslib.php');
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
use block_learnerscript\local\querylib;

class report_badges extends reportbase implements report {

    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        $this->columns = array('badges' => array('name', 'issuername', 'coursename', 'timecreated', 'description', 'criteria', 'recipients', 'expiredate'));
        $this->parent = true;
        $this->components = array('columns', 'filters', 'permissions', 'plot');
        $this->courselevel = false;
        $this->basicparams = array(['name' => 'coursecategories']);
        $this->filters = array('courses');
        $this->orderable = array('name');
    }
    public function get_all_elements() {
        global $DB, $USER;
        $params = array();
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
            $fields = array('b.issuername', 'b.name', 'c.fullname');
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $searchconcat .= " AND ($fields) ";
        }
        if (!empty($this->params['filter_courses'])) {
            $courseids = $this->params['filter_courses'];
            $searchconcat .= " AND b.courseid IN ($courseids)";
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
        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $searchconcat .= " AND b.timecreated BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            return array(array(), 0);
        }
        $badgesql = " ";
        $badgecountsql  = "SELECT COUNT(DISTINCT b.id) ";
        $badgeselectsql = "SELECT b.id, b.courseid, b.attachment, b.issuerurl, b.usercreated, b.expireperiod, c.category AS categoryid ";
        if (in_array('name', $this->selectedcolumns)) {
            $badgesql .= ", b.name AS name";
        }
        if (in_array('issuername', $this->selectedcolumns)) {
            $badgesql .= ", b.issuername AS issuername";
        }
        if (in_array('timecreated', $this->selectedcolumns)) {
            $badgesql .= ", b.timecreated AS timecreated";
        }
        if (in_array('description', $this->selectedcolumns)) {
            $badgesql .= ", b.description AS description";
        }
        if (in_array('expiredate', $this->selectedcolumns)) {
            $badgesql .= ", b.expiredate AS expiredate";
        }
        $badgesql .= " FROM {badge} AS b
                        LEFT JOIN {course} AS c ON c.id = b.courseid AND c.visible = 1
                        WHERE  b.status != 0 AND b.status != 2 AND b.status != 4 
                        $searchconcat";

        try {
            $badgecount = $DB->count_records_sql($badgecountsql . $badgesql, $params);
        } catch (dml_exception $e) {
            $badgecount = 0;
        }
        try {
            if (!empty($this->sqlorder)) {
                $badgesql .= " ORDER BY ". $this->sqlorder;
            } else {
                $badgesql .= " ORDER BY b.id DESC";
            }
            $badges = $DB->get_records_sql($badgeselectsql . $badgesql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $badges = array();
        }
        return array($badges, $badgecount);
    }

    public function get_rows($badges) {
        global $DB, $CFG, $PAGE, $OUTPUT, $USER;
        $systemcontext = context_system::instance();
        $data = array();
        if (!empty($badges)) {
            foreach ($badges as $badge) {
                if (!$badge->id) {
                    continue;
                }
                $batchinstance = new badge($badge->id);
                $context = $batchinstance->get_context();
                $badgeimage = print_badge_image($batchinstance, $context);
                $getcriteria = $PAGE->get_renderer('core_badges');
                $criteria = $getcriteria->print_badge_criteria($batchinstance);
                $courserecord = $DB->get_record('course', array('id' => $badge->courseid));
                $userrecord = $DB->get_record('user', array('id' => $badge->userid));
                $completioninfo = new completion_info($courserecord);
                $activityinforeport = new stdClass();
                $params = array();
                $params['userid'] = $userid;
                $recipients = $DB->count_records_sql('SELECT COUNT(b.userid)
                                        FROM {badge_issued} b INNER JOIN {user} u ON b.userid = u.id
                                        WHERE b.badgeid = :badgeid AND u.deleted = 0 AND u.confirmed = 1
                                        ', array('badgeid' => $badge->id));
                if ($this->ls_startdate >= 0 && $this->ls_enddate) {
                    $datefiltersql = " AND gg.timemodified BETWEEN :startdate AND :enddate ";
                    $datesql = " AND timemodified BETWEEN :startdate AND :enddate ";
                    $params['startdate'] = $this->ls_startdate;
                    $params['enddate'] = $this->ls_enddate;
                }
                $activityinforeport->name = '<a href="' . $CFG->wwwroot . '/badges/overview.php?id=' . $badge->id .
                                            '" target="_blank" class="edit">' . $badgeimage . ' ' . $badge->name . '</a>';

                $activityinforeport->username = $userrecord->firstname .'  '. $userrecord->lastname;
                $activityinforeport->issuername = $badge->issuername;
                if ($badge->courseid = null || empty($badge->courseid)) {
                    $activityinforeport->coursename = $courserecord->fullname ? $courserecord->fullname : 'System';
                } else {
                    $reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'courseprofile'));
                    $profilepermissions = empty($reportid) ? false : (new reportbase($reportid))->check_permissions(
                                                                        $USER->id, $systemcontext);
                    if (empty($reportid) || empty($profilepermissions)) {
                        $activityinforeport->coursename = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courserecord->id.'" />'.$courserecord->fullname.'</a>';
                    } else {
                        $activityinforeport->coursename = '<a href="'.$CFG->wwwroot.'/blocks/learnerscript/viewreport.php?id='.$reportid.'&filter_courses='.$courserecord->id.'&filter_coursecategories='.$courserecord->category.'" target="_blank" class="edit">'.($courserecord->fullname ? $courserecord->fullname : 'System').'</a>';
                    }
                }

                $activityinforeport->timecreated = date('l, d F Y H:i A', $badge->timecreated);
                if (!empty($badge->expiredate)) {
                    $badgeexpiredate = date('l, d F Y', $badge->expiredate);
                } else if (!empty($badge->expireperiod)) {
                    if ($badge->expireperiod < 60) {
                        $badgeexpiredate = get_string('expireperiods', 'badges', round($badge->expireperiod, 2));
                    } else if ($badge->expireperiod < 60 * 60) {
                        $badgeexpiredate = get_string('expireperiodm', 'badges', round($badge->expireperiod / 60, 2));
                    } else if ($badge->expireperiod < 60 * 60 * 24) {
                        $badgeexpiredate = get_string('expireperiodh', 'badges', round($badge->expireperiod / 60 / 60, 2));
                    } else {
                        $badgeexpiredate = get_string('expireperiod', 'badges', round($badge->expireperiod / 60 / 60 / 24, 2));
                    }
                } else {
                    $badgeexpiredate = "--";
                }
                $activityinforeport->expiredate = $badgeexpiredate;
                $activityinforeport->description = $badge->description;
                $activityinforeport->criteria = $criteria;
                $activityinforeport->recipients = $badge->recipients;
                $activityinforeport->recipients = '<a href="'. $CFG->wwwroot. '/badges/recipients.php?id='.$badge->id.'" target="_blank" class="edit">'.$recipients.'</a>';
                $data[] = $activityinforeport;
            }
        }
        return $data;
    }
}