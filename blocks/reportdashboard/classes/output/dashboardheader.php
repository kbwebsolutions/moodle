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
 * LearnerScript Report Dashboard Header
 *
 * @package    block_reportdashboard
 * @copyright  2017 eAbyas Info Solutions
 * @license    http://www.gnu.org/copyleft/gpl.reportdashboard GNU GPL v3 or later
 */
namespace block_reportdashboard\output;

defined('MOODLE_INTERNAL') || die();
require_once $CFG->libdir . '/coursecatlib.php';
use renderable;
use renderer_base;
use templatable;
use stdClass;
use context_system;
use coursecat;
use block_learnerscript\local\ls as ls;
use block_reportdashboard\local\reportdashboard as reportdashboard;

class dashboardheader implements renderable, templatable {
    public $editingon;
    public function __construct($data) {
        $this->editingon = $data->editingon;
        $this->configuredinstances = $data->configuredinstances;
        isset($data->getdashboardname) ? $this->getdashboardname = $data->getdashboardname : null;
        $this->dashboardurl = $data->dashboardurl;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE, $USER;
        $data = array();
        $systemcontext = context_system::instance();
        if (!empty($_SESSION['role'])) {
            $data['currentrole'] = $_SESSION['role'];
            $data['dashboardrole'] = $_SESSION['role'];
        } else {
            $data['currentrole'] = 'Switch Role';
            $data['dashboardrole'] = '';
        }
        if (!is_siteadmin()) {
            $roles = (new ls)->get_currentuser_roles();
        } else {
            $roles = get_switchable_roles($systemcontext);
        }
        if (is_siteadmin() || count($roles) > 0) {
            $data['switchrole'] = true;
        }
        $unusedroles = array('user', 'guest', 'frontpage');
        foreach ($roles as $key => $value) {
            $roleshortname = $DB->get_field('role', 'shortname', array('id' => $key));
            if (in_array($roleshortname, $unusedroles)) {
                continue;
            }
            $active = '';
            if ($roleshortname == $_SESSION['role']) {
                $active = 'active';
            }
            switch ($value) {
                    case 'coursecreator':   $value = get_string('coursecreators'); break;
                    case 'editingteacher':  $value = get_string('defaultcourseteacher'); break;
                    case 'teacher':         $value = get_string('noneditingteacher'); break;
                    case 'student':         $value = get_string('defaultcoursestudent'); break;
                    case 'guest':           $value = get_string('guest'); break;
                    case 'user':            $value = get_string('authenticateduser'); break;
                    case 'frontpage':       $value = get_string('frontpageuser', 'role'); break;
                    // We should not get here, the role UI should require the name for custom roles!
                    default:                $value = $value; break;
                }
            $data['roles'][] = ['roleshortname' => $roleshortname, 'rolename' => $value,
                                'active' => $active];
        }
        $data['editingon'] = $this->editingon;
        $data['issiteadmin'] = is_siteadmin();
        if ($_SESSION['role'] == 'manager') {
            $data['managerrole'] = 'manager';
        }
        $data['dashboardurl'] = $this->dashboardurl;
        $data['configuredinstances'] = $this->configuredinstances;
        $dashboardlist = $this->get_dashboard_reportscount();
        $data['sesskey'] = sesskey();
        if (count($dashboardlist)) {
            $data['get_dashboardname'] = $dashboardlist;
        }

        $data['reporttilestatus'] = $PAGE->blocks->is_known_block_type('reporttiles', false);
        $data['reportdashboardstatus'] = $PAGE->blocks->is_known_block_type('reportdashboard', false);
        $data['reportwidgetstatus'] = ($data['reporttilestatus'] || $data['reportdashboardstatus']) ? true : false;

        if (is_siteadmin() || $_SESSION['role'] == 'manager') {
            $dashboardcoursecategories = coursecat::make_categories_list();
            // $dashboardcoursecategories = $DB->get_records_sql("SELECT id, name FROM {course_categories}");
        } else {
             $dashboardcoursecategories = $DB->get_records_sql("SELECT id, name FROM {course_categories} WHERE id IN (SELECT c.category FROM {course} c JOIN {enrol} e ON e.courseid = c.id
                 JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = $USER->id)");
        }
        $coursecategorieslist = [];

        if (is_siteadmin() || $_SESSION['role'] == 'manager') {
            foreach ($dashboardcoursecategories as $key => $dashboardcoursecategorie) {
                $coursecategorieslist[] = ['id' => $key, 'value' => $dashboardcoursecategorie];            
            }
        } else {
            foreach ($dashboardcoursecategories as $dashboardcoursecategorie) {
                $coursecategorieslist[] = ['id' => $dashboardcoursecategorie->id, 'value' => $dashboardcoursecategorie->name];            
            }
        }
        if (!empty($coursecategorieslist)) {
            $data['coursecategorieslist'] = $coursecategorieslist;
            $data['dashboard'] = 1;
        }
        return $data;
    }

    public function get_dashboard_reportscount() {
        global $DB;
        $role = $_SESSION['role'];
        if (!empty($role) && !is_siteadmin()) {
            $getreports = $DB->get_records_sql("SELECT DISTINCT(subpagepattern) FROM {block_instances}
            	            WHERE pagetypepattern LIKE '%blocks-reportdashboard-dashboard-$role%' ");
        } else {
            $getreports = $DB->get_records_sql("SELECT DISTINCT(subpagepattern) FROM {block_instances}
            	           WHERE pagetypepattern LIKE '%blocks-reportdashboard-dashboard%' ");
        }
        $dashboardname = array();
        $pagetypepatternarray = array();
        $i = 0;
        $rolelist = $DB->get_records_sql_menu("SELECT id, shortname FROM {role} ");
        if (!empty($getreports)) {
            foreach ($getreports as $getreport) {
                $dashboardname[$getreport->subpagepattern] = $getreport->subpagepattern;
            }
        } else {
            $dashboardname['Dashboard'] = 'Dashboard';
        }
        foreach ($dashboardname as $key => $value) {
            if ($value != 'Dashboard' && !(new reportdashboard)->is_dashboardempty($key)) {
                continue;
            }
            $concatsql = $DB->sql_like('subpagepattern', ':subpagepattern');
            $params = array();
            $params['subpagepattern'] = '%' . $key . '%';
            $getreports = $DB->count_records_sql("SELECT COUNT(id) FROM {block_instances} WHERE $concatsql ", $params);
            $getdashboardname[$i]['name'] = ucfirst($value);
            $getdashboardname[$i]['pagetypepattern'] = $value;
            $getdashboardname[$i]['counts'] = $getreports;
            $getdashboardname[$i]['random'] = $i;
            if ($value == 'Dashboard') {
                $getdashboardname[$i]['default'] = 0;
            } else {
                $getdashboardname[$i]['default'] = 1;
            }
            $i++;
        }
        return $getdashboardname;
    }
}