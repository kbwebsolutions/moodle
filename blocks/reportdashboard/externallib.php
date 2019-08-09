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
 * LearnerScript Dashboard block plugin installation.
 *
 * @package    block_reportdashboard
 * @author     Arun Kumar Mukka
 * @copyright  2018 eAbyas Info Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once $CFG->libdir . '/coursecatlib.php';
define('AJAX_SCRIPT', true);
use block_learnerscript\local\ls;
use block_learnerscript\local\reportbase;
use block_reportdashboard\local\reportdashboard;
global $CFG, $DB, $USER, $OUTPUT, $COURSE;
require_login();
class block_reportdashboard_external extends external_api {
    public static function userlist_parameters() {
        return new external_function_parameters(
            array(
                'term' => new external_value(PARAM_TEXT, 'The current search term in the search box', false, ''),
                '_type' => new external_value(PARAM_TEXT, 'A "request type", default query', false, ''),
                'query' => new external_value(PARAM_TEXT, 'Query', false, ''),
                'action' => new external_value(PARAM_TEXT, 'Action', false, ''),
                'userlist' => new external_value(PARAM_TEXT, 'Users list', false, ''),
                'reportid' => new external_value(PARAM_INT, 'Report ID', false, 0),
                'maximumSelectionLength' => new external_value(PARAM_INT, 'Maximum Selection Length to Search', false, 0),
                'setminimumInputLength' => new external_value(PARAM_INT, 'Minimum Input Length to Search', false, 2),
                'categoryid' => new external_value(PARAM_INT, 'Query', false, 0)
            )
        );
    }
    public static function userlist($term, $_type, $query, $action, $userlist, $reportid,
            $maximumSelectionLength, $setminimumInputLength, $categoryid) {
        global $DB;
        $users = get_users(true, $term, true);
        $reportclass = (new ls)->create_reportclass($reportid);
        $reportclass->courseid = $reportclass->config->courseid;
        if ($reportclass->config->courseid == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($reportclass->config->courseid);
        }
        $data = array();

        $components = (new ls)->cr_unserialize($reportclass->config->components);
        $permissions = (isset($components['permissions'])) ? $components['permissions'] : array();
        foreach ($users as $user) { 
            if ($user->id > 2) {
                $ids = [$categoryid];
                $category = \coursecat::get($categoryid);
                $categoryids = array_merge($ids, $category->get_all_children_ids());
                $catids = implode(',', $categoryids);
                $categoryusers = "SELECT u.* 
                                    FROM {user} u
                                    LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
                                    LEFT JOIN {enrol} e ON e.id = ue.enrolid
                                    LEFT JOIN {course} c ON c.id = e.courseid
                                    WHERE u.confirmed = 1 AND u.deleted = 0 AND u.id = $user->id AND c.category IN ($catids)";
                $categoryuser = $DB->get_record_sql($categoryusers);
                if (!empty($categoryuser)) {
                    $userroles = (new ls)->get_currentuser_roles($categoryuser->id);
                    $reportclass->userroles = $userroles;
                    if ($reportclass->check_permissions($categoryuser->id, $context)) {
                        $data[] = ['id' => $categoryuser->id, 'text' => fullname($categoryuser)];
                    }
                }
            } else {
                $userroles = (new ls)->get_currentuser_roles($user->id);
                $reportclass->userroles = $userroles;
                if ($reportclass->check_permissions($user->id, $context)) {
                    $data[] = ['id' => $user->id, 'text' => fullname($user)];
                }

            }          
            
        }

        $return = ['total_count' => count($data), 'items' => $data];
        $data = json_encode($return);
        return $data;
    }
    public static function userlist_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

    public static function reportlist_parameters() {
        return new external_function_parameters(
            array(
                'search' => new external_value(PARAM_RAW, 'Search value', false, ''),
            )
        );
    }
    public static function reportlist($search) {
        $context = context_system::instance();
        $search = 'admin';
        $sql = "SELECT id, name FROM {block_learnerscript} WHERE visible = 1 AND name LIKE '%$search%'";
        $courselist = $DB->get_records_sql($sql);
        $activitylist = array();
        foreach ($courselist as $cl) {
            global $CFG;
            if (!empty($cl)) {
                $checkpermissions = (new reportbase($cl->id))->check_permissions($USER->id, $context);
                if (!empty($checkpermissions) || has_capability('block/learnerscript:managereports', $context)) {
                    $modulelink = html_writer::link(new moodle_url('/blocks/learnerscript/viewreport.php',
                                array('id' => $cl->id)), $cl->name, array('id' => 'viewmore_id'));
                    $activitylist[] = ['id' => $cl->id, 'text' => $modulelink];
                }
            }
        }
        $termsdata = array();
        $termsdata['total_count'] = count($activitylist);
        $termsdata['incomplete_results'] = true;
        $termsdata['items'] = $activitylist;
        $return = $termsdata;
        $data = json_encode($return);
        return $data;
    }

    public static function reportlist_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

    public static function sendemails_parameters() {
        return new external_function_parameters(
            array(
                'reportid' => new external_value(PARAM_INT, 'Report ID', false, 0),
                'instance' => new external_value(PARAM_INT, 'Reprot Instance', false),
                'pageurl' => new external_value(PARAM_LOCALURL, 'Page URL', false, ''),
            )
        );

    }
    public static function sendemails($reportid, $instance, $pageurl) {
        global $CFG, $PAGE;
        $PAGE->set_context(context_system::instance());
        $pageurl = $pageurl ? $pageurl : $CFG->wwwroot . '/blocks/reportdashboard/dashboard.php';
        require_once($CFG->dirroot . '/blocks/reportdashboard/email_form.php');
        $emailform = new analytics_emailform($pageurl, array('reportid' => $reportid, 'AjaxForm' => true, 'instance' => $instance));
        $return = $emailform->render();
        $data = json_encode($return);
        return $data;
    }

    public static function sendemails_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

    public static function inplace_editable_dashboard_parameters() {
        return new external_function_parameters(
            array(
                'prevoiusdashboardname' => new external_value(PARAM_TEXT, 'The Prevoius Dashboard Name', false, ''),
                'pagetypepattern' => new external_value(PARAM_TEXT, 'The Page Patten Type', false, ''),
                'subpagepattern' => new external_value(PARAM_TEXT, 'The Sub Page Patten Type', false, ''),
                'value' => new external_value(PARAM_TEXT, 'The Dashboard Name', false, ''),
            )
        );
    }
    public static function inplace_editable_dashboard($prevoiusdashboardname, $pagetypepattern, $subpagepattern, $value) {
        global $DB, $PAGE;
        $explodepetten = explode('-', $pagetypepattern);
        $dashboardname = str_replace (' ', '', $value);
        if (strlen($dashboardname) > 30 || empty($dashboardname)) {
            return $prevoiusdashboardname;
        }
        $update = $DB->execute("UPDATE {block_instances} SET subpagepattern = '$dashboardname' WHERE subpagepattern = '$subpagepattern'");
        if ($update) {
            return $dashboardname;
        } else {
            return false;
        }
    }
    public static function inplace_editable_dashboard_returns() {
        return new external_value(PARAM_RAW, 'data');
    }
}
