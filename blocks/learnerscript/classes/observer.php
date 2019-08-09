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
 * Event observer.
 *
 * @package    block_learnerscript
 * @copyright
 */

defined('MOODLE_INTERNAL') || die();

class block_learnerscript_observer {

    /**
     * Store all actions about modules create/update/delete in own table.
     *
     * @param \core\event\base $event
     */
    public static function store(\core\event\base $event) {
        global $CFG, $DB, $USER;
        if (!is_siteadmin()) {
            $browscap = new block_learnerscript_browscap($CFG->dataroot . '/cache/');
            $browscap->doAutoUpdate = false;
            $info = $browscap->getBrowser();
            $ipinfo =  unserialize(file_get_contents('http://ip-api.com/php/' . $_SERVER['REMOTE_ADDR']));
            $accessip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $ipinfocountrycode = isset($ipinfo['countryCode']) ? $ipinfo['countryCode'] : '';
            $infobrowser = isset($info->Browser) ? $info->Browser : '';
            $ipinofregion = isset($ipinfo['region']) ? $ipinfo['region'] : '';
            $device = $DB->get_record('block_devicels',  array('userid' => $USER->id, 'accessip' => $accessip, 'countrycode' => $ipinfocountrycode, 'browser' => $infobrowser, 'region' => $ipinofregion));
            if(!$device){
                if (!$DB->record_exists('block_devicels',  array('accessip' => $accessip, 'userid' => $USER->id))) {
                    $deviceinfo = new stdClass;
                    if ($ipinfo && $ipinfo['status'] == 'success') {
                        $deviceinfo->accessip = isset($ipinfo['query']) ? $ipinfo['query'] : '';
                        $deviceinfo->country = isset($ipinfo['country']) ? $ipinfo['country'] : '';
                        $deviceinfo->countrycode = strtoupper($ipinfocountrycode);
                        $deviceinfo->region = $ipinofregion;
                        $deviceinfo->regionName = isset($ipinfo['regionName']) ? $ipinfo['regionName'] : '';
                        $deviceinfo->city = isset($ipinfo['city']) ? $ipinfo['city'] : '';
                    }
                } else {
                    $deviceinfo = $DB->get_record_sql('SELECT accessip, country, countryCode, region, regionName, city FROM {block_devicels}
                        WHERE accessip = "' .$accessip . '"');
                }
                $deviceinfo->userid = $USER->id;
                $deviceinfo->browser = $infobrowser;
                $deviceinfo->browserparent = isset($info->Parent) ? $info->Parent : '';
                $deviceinfo->platform = isset($info->Platform) ? $info->Platform : '';
                $deviceinfo->browserversion = isset($info->Version) ? $info->Version : '';  
                $deviceinfo->devicetype = isset($info->Device_Type) ? $info->Device_Type : '';
                $deviceinfo->pointingmethod = isset($info->Device_Pointing_Method) ? $info->Device_Pointing_Method : '';
                $deviceinfo->ismobiledevice = isset($info->isMobileDevice) ? $info->isMobileDevice : 0;
                $deviceinfo->istablet = isset($info->isTablet) ? $info->isTablet : 0;
                $deviceinfo->timemodified = time();
                $DB->insert_record('block_devicels',  $deviceinfo);
            }else{
                $deviceinfo = new stdClass;
                $deviceinfo->id = $device->id;
                $deviceinfo->timemodified = time();
                $DB->update_record('block_devicels',  $deviceinfo);
            }
        }
    }

    public static function ls_timestats(){
        global $CFG, $COURSE, $USER, $OUTPUT, $DB, $PAGE;
        $reluser = \core\session\manager::is_loggedinas() ? $GLOBALS['USER']->realuser : null;

        if ($USER && is_siteadmin($reluser) || $reluser) {
            return true;
        }

        $contextinstanceid = $PAGE->context->instanceid;
        if ($PAGE->context->contextlevel == 70 && $PAGE->context->instanceid > 0) {
            $modulename = $DB->get_field_sql("SELECT m.name FROM {course_modules} cm JOIN {modules} m 
                ON m.id = cm.module WHERE cm.id = $contextinstanceid");                
            if($modulename == 'scorm'){
                return false;
            }
        }
        $insertdata = new stdClass();
        $insertdata->sessionid = isset($_SESSION['USER']->sesskey) ? $_SESSION['USER']->sesskey : '';
        $insertdata->userid = isset($_SESSION['USER']->id) ? $_SESSION['USER']->id : 0;
        $insertdata->courseid = isset($_SESSION['courseid']) ? $_SESSION['courseid'] : SITEID;
        $insertdata->instanceid = isset($_SESSION['instanceid']) ? $_SESSION['instanceid'] : 0;
        $insertdata->activityid = isset($_SESSION['contextinstanceid']) ? $_SESSION['contextinstanceid'] : 0;
        $insertdata->timespent = isset($_COOKIE['time_timeme']) ? $_COOKIE['time_timeme'] : '';
        $insertdata1 = new stdClass();
        $insertdata1->sessionid = isset($_SESSION['USER']->sesskey) ? $_SESSION['USER']->sesskey : '';
        $insertdata1->userid = isset($_SESSION['USER']->id) ? $_SESSION['USER']->id : 0;
        $insertdata1->courseid = isset($_SESSION['courseid']) ? $_SESSION['courseid'] : SITEID;
        $insertdata1->timespent = isset($_COOKIE['time_timeme']) ? $_COOKIE['time_timeme'] : '';
        if (isset($_COOKIE['time_timeme']) && isset($_SESSION['pageurl_timeme']) &&
            $_COOKIE['time_timeme'] != 0) {

            $record1 = $DB->get_record('block_ls_coursetimestats',
                array('sessionid' => $insertdata1->sessionid,
                    'courseid' => $insertdata1->courseid,
                    'userid' => $insertdata1->userid));
            
            if ($record1) {
                $insertdata1->id = $record1->id;
                $insertdata1->timespent += $record1->timespent;
                $insertdata1->timemodified = time();
                $DB->update_record('block_ls_coursetimestats', $insertdata1);
            } else {
                $insertdata1->timecreated = time();
                $insertdata1->timemodified = 0;
                $DB->insert_record('block_ls_coursetimestats', $insertdata1);
            }

            $record = $DB->get_record('block_ls_modtimestats',
                array('sessionid' => $insertdata->sessionid,
                    'courseid' => $insertdata->courseid,
                    'activityid' => $insertdata->activityid,
                    'instanceid' => $insertdata->instanceid,
                    'userid' => $insertdata->userid));
            if ($PAGE->context->contextlevel == 70 && $insertdata->instanceid <> 0) {
                if ($record) {
                    $insertdata->id = $record->id;
                    $insertdata->timespent += $record->timespent;
                    $insertdata->timemodified = time();
                    $DB->update_record('block_ls_modtimestats', $insertdata);
                } else {
                    $insertdata->timecreated = time();
                    $insertdata->timemodified = 0;
                    $DB->insert_record('block_ls_modtimestats', $insertdata);
                }
            }

        } else {
            $_COOKIE['time_timeme'] = 0;
            $_SESSION['pageurl_timeme'] = parse_url($_SERVER['REQUEST_URI'])['path'];
            $_SESSION['time_timeme'] = round($_COOKIE['time_timeme'], 0);

        }
        $instance = 0;
        if ($PAGE->context->contextlevel == 70) {
            $cm = get_coursemodule_from_id('', $PAGE->context->instanceid);
            $instance = $cm->instance;
        }

        $_SESSION['courseid'] = $COURSE->id;
        $_SESSION['pageurl_timeme'] = parse_url($_SERVER['REQUEST_URI'])['path'];
        $_SESSION['contextlevel'] = $PAGE->context->contextlevel;
        $_SESSION['instanceid'] = $instance;
        $_SESSION['contextid'] = $PAGE->context->id;
        $_SESSION['contextinstanceid'] = $PAGE->context->instanceid;
        $PAGE->requires->js_call_amd('block_learnerscript/track', 'timeme');
        $_COOKIE['time_timeme'] = 0;
    }
}