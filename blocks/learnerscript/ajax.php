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
 * @author: Naveen Kumar <naveen@eabyas.in>
 */
define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/learnerscript/lib.php');
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\ls;
use block_learnerscript\local\reportbase;
use block_learnerscript\local\schedule;

global $CFG, $DB, $USER, $OUTPUT, $PAGE;

$rawjson = file_get_contents('php://input');

$requests = json_decode($rawjson, true);

foreach ($requests as $key => $val) {
    if (strpos($key, 'filter_') !== false) {
        $_POST[$key] = $val;
    }
}

$action = $requests['action'];
$reportid = optional_param('reportid', $requests['reportid'], PARAM_INT);
$scheduleid = optional_param('scheduleid', $requests['scheduleid'], PARAM_INT);
$selectedroleid = optional_param('selectedroleid', $requests['selectedroleid'], PARAM_RAW);
$roles = optional_param('roleid', $requests['roleid'], PARAM_RAW);
$search = optional_param('search', $requests['term'], PARAM_TEXT);
$type = optional_param('type', $requests['type'], PARAM_TEXT);
$schuserslist = optional_param('schuserslist', $requests['schuserslist'], PARAM_RAW);
$bullkselectedusers = optional_param('bullkselectedusers', $requests['bullkselectedusers'], PARAM_RAW);
$licencekey = optional_param('licencekey', $requests['licencekey'], PARAM_RAW);
$expireddate = optional_param('validdate', $requests['validdate'], PARAM_RAW);
$page = optional_param('page', $requests['page'], PARAM_INT);
$singleplot = optional_param('singleplot', $requests['singleplot'], PARAM_RAW);
$start = optional_param('start', $requests['start'], PARAM_INT);
$length = optional_param('length', $requests['length'], PARAM_INT);
$courseid = optional_param('courseid', $requests['courseid'], PARAM_INT);
$frequency = optional_param('frequency', $requests['frequency'], PARAM_INT);
$instance = optional_param('instance', $requests['instance'], PARAM_INT);
$cmid = optional_param('cmid', $requests['cmid'], PARAM_INT);
$status = optional_param('status', $requests['status'], PARAM_TEXT);
$userid = optional_param('userid', $requests['userid'], PARAM_INT);
$components = optional_param('components', $requests['components'], PARAM_RAW);
$component = optional_param('component', $requests['component'], PARAM_RAW);
$pname = optional_param('pname', $requests['pname'], PARAM_RAW);
$jsonformdata = optional_param('jsonformdata', $requests['jsonformdata'], PARAM_RAW);
$conditionsdata = optional_param('conditions', $requests['conditions'], PARAM_RAW);
$advancedcolumn = optional_param('advancedcolumn', $requests['advancedcolumn'], PARAM_RAW);
$export = optional_param('export', $requests['export'], PARAM_RAW);
$datefilter = optional_param('datefilter', $requests['datefilter'], PARAM_RAW);
$ls_fstartdate = optional_param('ls_fstartdate', $requests['ls_fstartdate'], PARAM_RAW);
$ls_fenddate = optional_param('ls_fenddate', $requests['ls_fenddate'], PARAM_RAW);
$cid = optional_param('cid', $requests['cid'], PARAM_RAW);
$reporttype = optional_param('reporttype', $requests['reporttype'], PARAM_RAW);
$components = optional_param('components', $requests['components'], PARAM_RAW);
$categoryid = optional_param('categoryid', $requests['categoryid'], PARAM_RAW);
$filters = optional_param('filters', $requests['filters'], PARAM_RAW);
$filters = json_decode($filters, true);
$basicparams = optional_param('basicparams', $requests['basicparams'], PARAM_RAW);
$basicparams = json_decode($basicparams, true);
$elementsorder = optional_param('elementsorder', $requests['elementsorder'], PARAM_RAW);

$context = context_system::instance();
require_login();
$PAGE->set_context($context);

$scheduling = new schedule();
$learnerscript = $PAGE->get_renderer('block_learnerscript');

switch ($action) {
case 'rolewiseusers':
	if ((has_capability('block/learnerscript:managereports', $context) || has_capability('block/learnerscript:manageownreports', $context) || is_siteadmin()) && !empty($roles)) {
		$user_list = $scheduling->rolewiseusers($roles, $search);
		$terms_data = array();
		$terms_data['page'] = $page;
		$terms_data['search'] = $search;
		$terms_data['total_count'] = sizeof($user_list);
		$terms_data['incomplete_results'] = false;
		$terms_data['items'] = $user_list;
		$return = $terms_data;
	} else {
		$terms_data = array();
		$terms_data['error'] = true;
		$terms_data['type'] = 'Warning';
		if (empty($roles)) {
			$terms_data['cap'] = false;
			$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'Role');
		} else {
			$terms_data['cap'] = true;
			$terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
		}
		$return = $terms_data;
	}
	break;
case 'roleusers':
	if ((has_capability('block/learnerscript:managereports', $context) || has_capability('block/learnerscript:manageownreports', $context) || is_siteadmin()) && !empty($reportid) && !empty($type) && !empty($roles)) {
		$userslist = $scheduling->schroleusers($reportid, $scheduleid, $type, $roles, $search, $bullkselectedusers);
		$terms_data = array();
		$terms_data['total_count'] = sizeof($userslist);
		$terms_data['incomplete_results'] = false;
		$terms_data['items'] = $userslist;
		$return = $terms_data;
	} else {
		$terms_data = array();
		$terms_data['error'] = true;
		$terms_data['type'] = 'Warning';
		if (empty($reportid)) {
			$terms_data['cap'] = false;
			$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'ReportID');
		} else if (empty($type)) {
			$terms_data['cap'] = false;
			$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'Type');
		} else if (empty($roles)) {
			$terms_data['cap'] = false;
			$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'Role');
		} else {
			$terms_data['cap'] = true;
			$terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
		}
		$return = $terms_data;
	}
	break;
case 'viewschuserstable':
	if ((has_capability('block/learnerscript:managereports', $context) || has_capability('block/learnerscript:manageownreports', $context) || is_siteadmin()) && !empty($schuserslist)) {
		$stable = new stdClass();
		$stable->table = true;
		$return = $learnerscript->viewschusers($reportid, $scheduleid, $schuserslist, $stable);
	} else {
		$terms_data = array();
		$terms_data['error'] = true;
		$terms_data['type'] = 'Warning';
		if (empty($schuserslist)) {
			$terms_data['cap'] = false;
			$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'Schedule Users List');
		} else {
			$terms_data['cap'] = true;
			$terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
		}
		$return = $terms_data;
	}
	break;
case 'manageschusers':
	if ((has_capability('block/learnerscript:managereports', $context) || has_capability('block/learnerscript:manageownreports', $context) || is_siteadmin()) && !empty($reportid)) {
		$reqimage = $OUTPUT->image_url('req');
		//'alt' => get_string('requiredelement', 'form'), 'class' => 'icon', 'title' => get_string('requiredelement', 'form')));

		$roles_list = (new schedule)->reportroles($selectedroleid);
		$selectedusers = (new schedule)->selectesuserslist($schuserslist);
		$scheduledata = new \block_learnerscript\output\scheduledusers($reportid,
			$reqimage,
			$roles_list,
			$selectedusers,
			$scheduleid);
		$return = $learnerscript->render($scheduledata);
		// $return = $learnerscript->scheduleusers($reportid, $scheduleid, $selectedroleid, $schuserslist);
	} else {
		$terms_data = array();
		$terms_data['error'] = true;
		$terms_data['type'] = 'Warning';
		if (empty($reportid)) {
			$terms_data['cap'] = false;
			$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'ReportID');
		} else {
			$terms_data['cap'] = true;
			$terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
		}
		$return = $terms_data;
	}
	break;
case 'schreportform':
	// if ((has_capability('block/learnerscript:managereports', $context) || has_capability('block/learnerscript:manageownreports', $context) || is_siteadmin()) && !empty($reportid)) {
	// 	require_once $CFG->dirroot . '/blocks/learnerscript/components/scheduler/schedule_form.php';
	// 	$roles_list = $scheduling->reportroles();
	// 	list($schusers, $schusersids) = $scheduling->userslist($reportid, $scheduleid);
	// 	$exportoptions = (new ls)->cr_get_export_plugins();
	// 	$frequencyselect = $scheduling->get_options();
	// 	$scheduledreport = $DB->get_record('block_ls_schedule', array('id' => $scheduleid));
	// 	if (!empty($scheduledreport)) {
	// 		$schedule_list = $scheduling->getschedule($scheduledreport->frequency);
	// 	} else {
	// 		$schedule_list = array(null => '--SELECT--');
	// 	}
	// 	$scheduleform = new scheduled_reports_form($CFG->wwwroot . '/blocks/learnerscript/components/scheduler/schedule.php', array('id' => $reportid, 'scheduleid' => $scheduleid, 'AjaxForm' => true, 'roles_list' => $roles_list,
	// 		'schusers' => $schusers, 'schusersids' => $schusersids, 'exportoptions' => $exportoptions, 'schedule_list' => $schedule_list, 'frequencyselect' => $frequencyselect, 'instance' => $instance));

	// 	$return = $scheduleform->render();
	// } else {
	// 	$terms_data = array();
	// 	$terms_data['error'] = true;
	// 	$terms_data['type'] = 'Warning';
	// 	if (empty($reportid)) {
	// 		$terms_data['cap'] = false;
	// 		$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'ReportID');
	// 	} else {
	// 		$terms_data['cap'] = true;
	// 		$terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
	// 	}
	// 	$return = $terms_data;
	// }
	$args = new stdClass();
	$args->reportid = $reportid;
	$args->instance = $instanceid;
	$args->jsonformdata = $jsonformdata;
	$return = block_learnerscript_schreportform_ajaxform($args);

	break;
case 'scheduledtimings':
	if ((has_capability('block/learnerscript:managereports', $context) || has_capability('block/learnerscript:manageownreports', $context) || is_siteadmin()) && !empty($reportid)) {
		$return = $learnerscript->schedulereportsdata($reportid, $courseid, false, $start, $length, $search['value']);
	} else {
		$terms_data = array();
		$terms_data['error'] = true;
		$terms_data['type'] = 'Warning';
		if (empty($reportid)) {
			$terms_data['cap'] = false;
			$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'ReportID');
		} else {
			$terms_data['cap'] = true;
			$terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
		}
		$return = $terms_data;
	}
	break;
case 'generate_plotgraph':
	if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
		print_error('reportdoesnotexists', 'block_learnerscript');
	}
	require_once($CFG->dirroot . '/blocks/learnerscript/reports/' . $report->type . '/report.class.php');
	$reportclassname = 'report_' . $report->type;
	$properties = new stdClass();
	$properties->cmid = $cmid;
	$properties->courseid = $courseid;
	$properties->userid = $userid;
	$properties->status = $status;
	if (!empty($ls_fstartdate)) {
		$properties->ls_startdate = $ls_fstartdate;
	} else {
		$properties->ls_startdate = 0;
	}

	if (!empty($ls_enddate)) {
		$properties->ls_enddate = $ls_fenddate;
	} else {
		$properties->ls_enddate = time();
	}
	$reportclass = new $reportclassname($report, $properties);

	$reportclass->create_report();
	$components = (new ls)->cr_unserialize($reportclass->config->components);
	if ($singleplot == 'table') {
		$datacolumns = array();
		$columnDefs = array();
		$i = 0;
		foreach ($reportclass->finalreport->table->head as $key => $value) {
			$datacolumns[]['data'] = $value;
			$columnDef = new stdClass();
			$align = $reportclass->finalreport->table->align[$i] ? $reportclass->finalreport->table->align[$i] : 'left';
			$wrap = ($reportclass->finalreport->table->wrap[$i] == 'wrap') ? 'break-all' : 'normal';
			$width = ($reportclass->finalreport->table->size[$i]) ? $reportclass->finalreport->table->size[$i] : '';
			$columnDef->className = 'dt-body-' . $align;
			$columnDef->targets = [$i];
			$columnDef->wrap = $wrap;
			$columnDef->width = $width;
			$columnDefs[] = $columnDef;
			$i++;
		}
		if (!empty($reportclass->finalreport->table->head)) {
			$tablehead = (new ls)->report_tabledata($reportclass->finalreport->table);
			$reporttable = new \block_learnerscript\output\reporttable($tablehead,
				$reportclass->finalreport->table->id,
				'',
				$reportid,
				$reportclass->sql,
				false,
				false,
				null,
				$report->type
			);
			$return = array();
			$return['tdata'] = $learnerscript->render($reporttable);
			$return['columnDefs'] = $columnDefs;
		} else {
			$return['tdata'] = '<div class="alert alert-info">' . get_string("nodataavailable", "block_learnerscript") . '</div>';
		}
	} else {
		$seriesvalues = (isset($components['plot']['elements'])) ? $components['plot']['elements'] : array();
		$i = 0;
		foreach ($seriesvalues as $g) {
			if (($singleplot != '' && $g['id'] == $singleplot) || $i == 0) {
				$return['plot'] = (new ls)->generate_report_plot($reportclass, $g);
				if ($singleplot != '' && $g['id'] == $singleplot) {
					break;
				}
			}
			$return['plotoptions'][] = array('id' => $g['id'], 'title' => $g['formdata']->chartname, 'pluginname' => $g['pluginname']);
			$i++;
		}
	}
	break;
case 'pluginlicence':
	if (!empty($expireddate) && !empty($licencekey)) {
		$explodedatetime = explode(' ', $expireddate);
		$explodedate = explode('-', $explodedatetime[0]);
		$explodetime = explode(':', $explodedatetime[1]);
		$expireddate = mktime($explodetime[0], $explodetime[1], $explodetime[2], $explodedate[1], $explodedate[2], $explodedate[0]);
		$return = $scheduling->insert_licence($licencekey, $expireddate);
	} else {
		$terms_data = array();
		$terms_data['error'] = true;
		$terms_data['cap'] = false;
		$terms_data['type'] = 'Warning';
		$terms_data['msg'] = get_string('licencemissing', 'block_learnerscript');
		$return = $terms_data;
	}
	break;
case 'frequency_schedule':
	$return = $scheduling->getschedule($frequency);
	break;
case 'reportobject':
	if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
		print_error('reportdoesnotexists', 'block_learnerscript');
	}
	require_once($CFG->dirroot . '/blocks/learnerscript/reports/' . $report->type . '/report.class.php');
	$reportclassname = 'report_' . $report->type;
	$properties = new stdClass();
	$reportclass = new $reportclassname($report, $properties);
	$reportclass->create_report();
	$return = (new ls)->cr_unserialize($reportclass->config->components);
	break;
case 'updatereport':
	if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
		print_error('reportdoesnotexists', 'block_learnerscript');
	}
	require_once($CFG->dirroot . '/blocks/learnerscript/reports/' . $report->type . '/report.class.php');
	$reportclassname = 'report_' . $report->type;
	$properties = new stdClass();
	$reportclass = new $reportclassname($report, $properties);
	$comp = (array) (new ls)->cr_unserialize($reportclass->config->components);
	$components = json_decode($components, true);
	$plugins = get_list_of_plugins('blocks/learnerscript/components/calcs');
	$orderingplugins = get_list_of_plugins('blocks/learnerscript/components/ordering');

	foreach ( $components['calculations']['elements'] as $k => $calculations) {
		if (empty($calculations['pluginname']) || ($calculations['type'] != 'calculations')){
			unset($components['calculations']['elements'][$k]);
		} else {
			$components['calculations']['elements'][$k]['formdata'] = (object) $components['calculations']['elements'][$k]['formdata'];
		}
	}

    $comp['columns']['elements'] = $components['columns']['elements'];
    $comp['filters']['elements'] = $components['filters']['elements'];
    $comp['calculations']['elements'] = $components['calculations']['elements'];
    $comp['ordering']['elements'] = $components['ordering']['elements'];
    $comparray = ['columns', 'filters', 'calculations', 'ordering'];
    foreach ($comparray as $c) {
        foreach ($comp[$c]['elements'] as $k => $d) {
            if ($c == 'filters') {
                if (empty($d['formdata']['value'])) {
                    unset($comp[$c]['elements'][$k]);
                    continue;
                }
            }
            if ($c == 'calculations') {
                $comp[$c]['elements'][$k]['formdata'] = (object) $comp[$c]['elements'][$k]['formdata'];
                if (empty($d['pluginname']) || ($d['type'] == 'selectedcolumns' && !in_array($d['pluginname'], $plugins)) || empty($comp[$c]['elements'][$k]['formdata'])) {
                    unset($comp[$c]['elements'][$k]);
                    continue;
                }
            }
            if ($c == 'ordering') {
                if (empty($d['pluginname']) || ($d['type'] == 'Ordering' && !in_array($d['pluginname'], $orderingplugins))) {
                    unset($comp[$c]['elements'][$k]);
                    continue;
                }
                unset($comp[$c]['elements'][$k]['orderingcolumn']);
            }
            if ($c != 'calculations') {
                $comp[$c]['elements'][$k]['formdata'] = (object) $d['formdata'];
            }
        }
        $comp['calculations']['elements'] = array_values($comp['calculations']['elements']);

	}
	$listofexports = $components['exports'];
	$exportlist = array();
	foreach ($listofexports as $key => $exportoptions) {
		if (!empty($exportoptions['value'])) {
			$exportlist[] = $exportoptions['name'];
		}
	}
	$exports = implode(',', $exportlist);
	$components = (new ls)->cr_serialize($comp);
	if (empty($listofexports)) {
		$DB->update_record('block_learnerscript', (object) ['id' => $reportid, 'components' => $components]);
	} else {
		$DB->update_record('block_learnerscript', (object) ['id' => $reportid, 'components' => $components, 'export' => $exports]);
	}
	break;
case 'plotforms':
	$args = new stdClass();
	$args->context = $context;
	$args->reportid = $reportid;
	$args->component = $component;
	$args->pname = $pname;
	$args->cid = $cid;
	$args->jsonformdata = $jsonformdata;

	$return = block_learnerscript_plotforms_ajaxform($args);

	break;
case 'updatereport_conditions':
	if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
		print_error('reportdoesnotexists', 'block_learnerscript');
	}
	$conditionsdata = json_decode($conditionsdata);
	$conditions = array();
	$conditions['elements'] = array();
	$sqlcon = array();
	$i = 1;
	foreach ($conditionsdata->selectedfields as $elementstr) {

		$element = explode(':', $elementstr);

		$columns = array();
		$columns['id'] = random_string();
		$columns['formdata'] = (object) ['field' => $element[1],
			'operator' => $conditionsdata->selectedcondition->{$elementstr},
			'value' => $conditionsdata->selectedvalue->{$elementstr},
			'submitbutton' => get_string('add')];
		$columns['pluginname'] = $element[0];
		$columns['pluginfullname'] = get_string($element[0], 'block_learnerscript');
		$columns['summary'] = get_string($element[0], 'block_learnerscript');
		$conditions['elements'][] = $columns;
		$sqlcon[] = 'c' . $i;
		$i++;
	}

	$conditions['config'] = (object) ['conditionexpr' => ($conditionsdata->sqlcondition) ? strtolower($conditionsdata->sqlcondition) : implode(' and ', $sqlcon),
		'submitbutton' => get_string('update')];

	$unserialize = (new ls)->cr_unserialize($report->components);
	$unserialize['conditions'] = $conditions;

	$unserialize = (new ls)->cr_serialize($unserialize);
	$DB->update_record('block_learnerscript', (object) ['id' => $reportid, 'components' => $unserialize]);
	break;
case 'reportcalculations':
	$checkpermissions = (new reportbase($reportid))->check_permissions($USER->id, $context);
	if ((has_capability('block/learnerscript:managereports', $context) || has_capability('block/learnerscript:manageownreports', $context) || !empty($checkpermissions)) && !empty($reportid)) {
		$properties = new stdClass();
		$reportclass = (new ls)->create_reportclass($reportid, $properties);
		$reportclass->params = array_merge($filters,$basicparams);
		$reportclass->start = 0;
		$reportclass->length = -1;
		$reportclass->colformat = true;
        $reportclass->calculations = true;
		$reportclass->create_report();
		$table = html_writer::table($reportclass->finalreport->calcs);
		$reportname = $DB->get_field('block_learnerscript', 'name', array('id' => $reportid));
		$return = ['table' => $table, 'reportname' => $reportname];
	} else {
		$terms_data = array();
		$terms_data['error'] = true;
		$terms_data['type'] = 'Warning';
		if (empty($reportid)) {
			$terms_data['cap'] = false;
			$terms_data['msg'] = get_string('missingparam', 'block_learnerscript', 'ReportID');
		} else {
			$terms_data['cap'] = true;
			$terms_data['msg'] = get_string('badpermissions', 'block_learnerscript');
		}
		$return = $terms_data;
	}
	break;
case 'advancedcolumns':
	$args = new stdClass();
	// $args->context = $context;
	$args->reportid = $reportid;
	$args->component = $component;
	$args->pname = $advancedcolumn;
	$args->jsonformdata = $jsonformdata;

	$return = block_learnerscript_plotforms_ajaxform($args);
	break;
case 'courseactivities':
	if ($courseid > 0 && $categoryid > 0) {
		$modinfo = get_fast_modinfo($courseid);
		$return[0] = 'Select Activity';
		if (!empty($modinfo->cms)) {
			foreach ($modinfo->cms as $k => $cm) {
				if($cm->visible == 1 && $cm->deletioninprogress == 0){
					$return[$k] = $cm->name;
				}
			}
		}
	} else {
		$return = [];
	}
	break;
case 'usercourses':
	if ($userid > 0 && $categoryid > 0) {
		$courselist = array_keys(enrol_get_users_courses($userid));
		if(!empty($courselist)) {
			$courseids = implode(',', $courselist);
			$courses = $DB->get_records_sql_menu("SELECT id, fullname FROM {course}
		                                           WHERE id <> 1 AND visible = 1 AND id IN ($courseids)");
			$return = array(0 => 'Select Course') + $courses;
		} else {
			//$return = array();
			$return = array('' => 'Select Course');
		}
	} else {
		$pluginclass = new stdClass;
		$pluginclass->singleselection = true;
		$pluginclass->report->type = $reporttype;
		$return = (new \block_learnerscript\local\querylib)->filter_get_courses($pluginclass);
	}
	break;
case 'enrolledusers':
	if ($courseid > 0 && $categoryid > 0) {
		$coursecontext = context_course::instance($courseid);
		$studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
		$enrolledusers = array_keys(get_enrolled_users($coursecontext));

		$return = array();
		if (!empty($enrolledusers)) {
			$enrolledusers = implode(',', $enrolledusers);
			$return = $DB->get_records_sql_menu("SELECT id, concat(firstname,' ',lastname) as name FROM {user}
	                                           WHERE confirmed = 1 AND deleted=0 AND id IN ($enrolledusers)");
		}
	} else {
		$pluginclass = new stdClass;
		$pluginclass->singleselection = true;
		$pluginclass->report->type = $reporttype;
		$pluginclass->report->components = $components;
		$return = (new \block_learnerscript\local\querylib)->filter_get_users($pluginclass, false);
	}
	break;
case 'categoryusers':
	if ($categoryid > 0) {
		$ids = [$categoryid];
        $category = \coursecat::get($categoryid);
        $categoryids = array_merge($ids, $category->get_all_children_ids());
        $catids = implode(',', $categoryids);
		$categoryusers = $DB->get_records_sql_menu("SELECT u.id, concat(u.firstname,' ',u.lastname) as name FROM {user} u
			JOIN {user_enrolments} ue ON ue.userid = u.id
			JOIN {enrol} e ON e.id = ue.enrolid
			JOIN {course} c ON c.id = e.courseid
            WHERE u.confirmed = 1 AND u.deleted = 0 AND u.id > 2 AND e.status = 0 AND ue. status = 0 AND c.category IN ($catids)");
		$return = array(0 => 'Select User') + $categoryusers;
	} else {
		$return = array('' => 'Select User');
	}
	break;
case 'categorycourses':
	if ($categoryid > 0) {
		    $ids = [$categoryid];
	        $category = \coursecat::get($categoryid);
	        $categoryids = array_merge($ids, $category->get_all_children_ids());
	        $catids = implode(',', $categoryids);
        if (is_siteadmin()) {
			$courses = $DB->get_records_sql_menu("SELECT id, fullname FROM {course} WHERE category IN ($catids)  AND visible = 1");
	    }else{
	     	$courselist = array_keys(enrol_get_users_courses($USER->id));
			if(!empty($courselist)) {
				$courseids = implode(',', $courselist);
				$courses = $DB->get_records_sql_menu("SELECT id, fullname FROM {course}
			                                           WHERE id <> 1 AND visible = 1 AND id IN ($courseids)AND category IN ($catids)");
				$return = array(0 => 'Select Course') + $courses;
		    }
	    }
		$return = array(0 => 'Select Course') + $courses;
	} else {
		$return = array('' => 'Select Course');
	}
	break;
case 'designdata':
	$return = array();
	if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
		print_error('reportdoesnotexists', 'block_learnerscript');
	}
	require_once($CFG->dirroot . '/blocks/learnerscript/reports/' . $report->type . '/report.class.php');
	$reportclassname = 'report_' . $report->type;

	$properties = new stdClass();
	$reportclass = new $reportclassname($report, $properties);

	$reportclass->cmid = $cmid || 0;
	$reportclass->courseid = $courseid || SITEID;
	$reportclass->userid = $userid || 0;
	$reportclass->start = 0;
	$reportclass->length = 5;

	if (!empty($ls_fstartdate)) {
		$reportclass->ls_startdate = $ls_fstartdate;
	} else {
		$reportclass->ls_startdate = 0;
	}

	if (!empty($ls_enddate)) {
		$reportclass->ls_enddate = $ls_fenddate;
	} else {
		$reportclass->ls_enddate = time();
	}
	$reportclass->preview = true;
	$reportclass->create_report(null);
	$components = unserialize($reportclass->config->components);
	$startTime = microtime(true);
	if ($report->type == 'sql') {
		$rows = $reportclass->get_rows();
		$return['rows'] = $rows['rows'];
		$reportclass->columns = get_object_vars($return['rows'][0]);
		$reportclass->columns = array_keys($reportclass->columns);
	} else {
		if (!isset($reportclass->columns)) {
			$availablecolumns = (new ls)->report_componentslist($report, 'columns');
		} else {
			$availablecolumns = $reportclass->columns + (new ls)->report_componentslist($report, 'columns');
		}
		// $reportTable = $reportclass->get_all_elements();
		//$return['rows'] = $reportclass->get_rows($reportTable[0]);
		$return['rows'] = $reportclass->finalreport->table->data;
	}

	$return['reportdata'] = json_encode($r, JSON_FORCE_OBJECT);
	/*
	 * Calculations data
	 */
	$comp = 'calcs';
	$plugins = get_list_of_plugins('blocks/learnerscript/components/' . $comp);
	$optionsplugins = array();
	foreach ($plugins as $p) {
		require_once($CFG->dirroot . '/blocks/learnerscript/components/' . $comp . '/' . $p . '/plugin.class.php');
		$pluginclassname = 'plugin_' . $p;
		$pluginclass = new $pluginclassname($report);
		if (in_array($report->type, $pluginclass->reporttypes)) {
			if ($pluginclass->unique && in_array($p, $currentplugins)) {
				continue;
			}

			$optionsplugins[get_string($p, 'block_learnerscript')] = $p;
		}
	}
	asort($optionsplugins);
	$return['calculations'] = $optionsplugins;
	$return['time'] .= "Calcluations Time:  " . number_format((microtime(true) - $startTime), 4) . " Seconds\n";
	//Selected columns
	$activecolumns = array();

	if (isset($components['columns']['elements'])) {
		foreach ($components['columns']['elements'] as $key => $value) {
			$value = (array) $value;
			$components['columns']['elements'][$key] = (array) $components['columns']['elements'][$key];

			$components['columns']['elements'][$key]['formdata']->columname = urldecode($value['formdata']->columname);
			$activecolumns[] = $value['formdata']->column;
		}
		$return['selectedcolumns'] = $components['columns']['elements'];
	} else {
		$return['selectedcolumns'] = array();
	}

	//========{conditions}===========
	$conditionsdata = array();
	if (isset($components->conditions->elements)) {
		foreach ($components->conditions->elements as $key => $value) {
			$conditionsdata[] = $value['formdata'];
		}
	}

	$plugins = get_list_of_plugins('blocks/learnerscript/components/conditions');
	$conditionscolumns = array();
	$conditionscolumns['elements'] = array();
	$conditionscolumns['config'] = array();
	foreach ($plugins as $p) {
		require_once($CFG->dirroot . '/blocks/learnerscript/components/conditions/' . $p . '/plugin.class.php');
		$pluginclassname = 'plugin_' . $p;
		$columns = array();
		$pluginclass = new $pluginclassname($report);
		if (in_array($report->type, $pluginclass->reporttypes)) {
			if ($pluginclass->unique && in_array($p, $currentplugins)) {
				continue;
			}
			$uniqueid = random_string(15);
			while (strpos($reportclass->config->components, $uniqueid) !== false) {
				$uniqueid = random_string(15);
			}
			$columns['id'] = $uniqueid;
			$columns['formdata'] = $conditionsdata;
			$columns['value'] = (in_array($p, $conditionsdata)) ? true : false;
			$columns['pluginname'] = $p;
			if (method_exists($pluginclass, 'columns')) {
				$columns['plugincolumns'] = $pluginclass->columns();
			} else {
				$columns['plugincolumns'] = array();
			}
			$columns['form'] = $pluginclass->form;
			$columns['allowedops'] = $pluginclass->allowedops;
			$columns['pluginfullname'] = get_string($p, 'block_learnerscript');
			$columns['summery'] = get_string($p, 'block_learnerscript');
			$conditionscolumns['elements'][$p] = $columns;
		}
	}
	$conditionscolumns['conditionssymbols'] = array("=", ">", "<", ">=", "<=", "<>", "LIKE", "NOT LIKE", "LIKE % %");
	if (!empty($components['conditions']['elements'])) {
		$finalelements = array();
		$finalelements['elements'] = array();
		$finalelements['selectedfields'] = array();
		$finalelements['selectedcondition'] = array();
		$finalelements['selectedvalue'] = array();
		$finalelements['sqlcondition'] = urldecode($components['conditions']['config']->conditionexpr);
		foreach ($components['conditions']['elements'] as $element) {
			$finalelements['elements'][] = $element['pluginname'];
			$finalelements['selectedfields'][] = $element['pluginname'] . ':' . $element['formdata']->field;
			$finalelements['selectedcondition'][$element['pluginname'] . ':' . $element['formdata']->field] = urldecode($element['formdata']->operator);
			$finalelements['selectedvalue'][$element['pluginname'] . ':' . $element['formdata']->field] = urldecode($element['formdata']->value);
		}
		$conditionscolumns['finalelements'] = $finalelements;
	}
	$return['conditioncolumns'] = $conditionscolumns;
	//========{conditions end}===========

	//Filters
	$filterdata = array();
	if (isset($components['filters']['elements'])) {
		foreach ($components['filters']['elements'] as $key => $value) {
			$value = (array) $value;
			if ($value['formdata']->value) {
				$filterdata[] = $value['pluginname'];
			}
		}
	}
	$filterplugins = get_list_of_plugins('blocks/learnerscript/components/filters');
	$filteroptions = array();
	if ($reportclass->config->type != 'sql') {
		$filterplugins = $reportclass->filters;
	}
	foreach ($filterplugins as $p) {
		require_once($CFG->dirroot . '/blocks/learnerscript/components/filters/' . $p . '/plugin.class.php');
		if (file_exists($CFG->dirroot . '/blocks/learnerscript/components/filters/' . $p . '/form.php')) {
			continue;
		}
		$pluginclassname = 'plugin_' . $p;
		$pluginclass = new $pluginclassname($report);
		// if (in_array($report->type, $pluginclass->reporttypes)) {
			$uniqueid = random_string(15);
			while (strpos($reportclass->config->components, $uniqueid) !== false) {
				$uniqueid = random_string(15);
			}
			$filtercolumns = array();
			$filtercolumns['id'] = $uniqueid;
			$filtercolumns['pluginname'] = $p;
			$filtercolumns['pluginfullname'] = get_string($p, 'block_learnerscript');
			$filtercolumns['summary'] = '';
			$columnss['name'] = get_string($p, 'block_learnerscript');
			$columnss['type'] = 'filters';
			$columnss['value'] = (in_array($p, $filterdata)) ? true : false;
			$filtercolumns['formdata'] = $columnss;
			$filterelements[] = $filtercolumns;
		// }
	}
	$return['filtercolumns'] = $filterelements;
	//Ordering
	$comp = 'ordering';
	$plugins = get_list_of_plugins('blocks/learnerscript/components/ordering');
	$orderingplugin = array();
	// foreach ($plugins as $p) {
	//     require_once($CFG->dirroot . '/blocks/learnerscript/components/' . $comp . '/' . $p . '/plugin.class.php');
	//     $pluginclassname = 'plugin_' . $p;
	//     $pluginclass = new $pluginclassname($report);
	//     if (in_array($report->type, $pluginclass->reporttypes)) {
	//         $orderingplugin[$p] = get_string($p, 'block_learnerscript') ;
	//     }
	// }
	asort($plugins);
	$orderingdata = array();

	foreach ($plugins as $key => $value) {
		require_once($CFG->dirroot . '/blocks/learnerscript/components/ordering/' . $value . '/plugin.class.php');
		$pluginclassname = 'plugin_' . $value;
		$pluginclass = new $pluginclassname($report);
		if (!in_array($report->type, $pluginclass->reporttypes)) {
			continue;
		}
		$tblcolumns = $pluginclass->columns();
		foreach ($components['ordering']['elements'] as $ordercomp) {
			if ($value == $ordercomp['pluginname']) {
				$ordercomp['pluginfullname'] = get_string($value, 'block_learnerscript');
				$ordercomp['orderingcolumn'] = array_keys($tblcolumns);
				$orderingdata[$value] = $ordercomp;
			}
		}
		if (!array_key_exists($value, $orderingdata)) {
			$uniqueid = random_string(15);
			while (strpos($reportclass->config->components, $uniqueid) !== false) {
				$uniqueid = random_string(15);
			}

			$ordering = array();
			$ordering['type'] = 'Ordering';
			$ordering['orderingcolumn'] = array_keys($tblcolumns);
			$ordering['pluginname'] = $value;
			$ordering['pluginfullname'] = get_string($value, 'block_learnerscript');
			$ordering['id'] = $uniqueid;
			$orderingdata[$value] = $ordering;
		}
	}
	$orderingdata = array_values($orderingdata);
	$return['ordercolumns'] = $orderingdata;
	//Columns
	if ($report->type == 'sql') {
		$columns = array();
		foreach ($reportclass->columns as $value) {
			$c = [];
			$uniqueid = random_string(15);
			while (strpos($reportclass->config->components, $uniqueid) !== false) {
				$uniqueid = random_string(15);
			}
			$c['id'] = $uniqueid;
			$c['pluginname'] = 'sql';
			$c['pluginfullname'] = 'SQL';
			$c['summary'] = '';

			if (in_array($value, $activecolumns)) {
				$columns['value'] = true;
				$c['type'] = 'selectedcolumns';
			} else {
				$columns['value'] = false;
				$c['type'] = 'columns';
			}
			$columns['columname'] = $value;
			$columns['column'] = $value;
			$columns['heading'] = '';
			$columns['wrap'] = '';
			$columns['align'] = '';
			$columns['size'] = '';
			$c['formdata'] = $columns;
			$elements[] = $c;
		}
	} else {
		$comp = 'columns';
		$cid = '';
		require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/component.class.php');
		$compclass = new component_columns($report->id);
		$i = 0;
		foreach ($availablecolumns as $key => $values) {
			if (!isset($reportclass->columns)) {
				$c = [];
				$c['formdata']->column = $key;
				$c['formdata']->columnname = get_string($key, 'block_learnerscript');
				$elements[] = $c;
			} else {
				$columns = array();
				foreach ($values as $value) {
					$c = [];
					$columnform = new stdClass;
					$classname ='';
					$uniqueid = random_string(15);
					while (strpos($reportclass->config->components, $uniqueid) !== false) {
						$uniqueid = random_string(15);
					}
					$c['id'] = $uniqueid;
					$c['pluginname'] = $key;
					$c['pluginfullname'] = get_string($key, 'block_learnerscript');
					$c['summary'] = '';
					if (in_array($value, $activecolumns)) {
							$type = 'selectedcolumns';
						}else{
							$type = 'columns';
						}
					if (file_exists($CFG->dirroot . '/blocks/learnerscript/components/columns/' . $value . '/plugin.class.php')) {
						require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/' . $value . '/plugin.class.php');
						$classname = 'plugin_'.$value;
						$columnform = new $classname($report);
						if ($columnform->type == 'advanced') {
							$c = [];
							$c['formdata']->column = $value;
							$c['formdata']->columnname = get_string($key, 'block_learnerscript');
							$elements[] = $c;
							continue;
						} else {
							$c['type'] = $type;
						}
					} else {
						$c['type'] = $type;
					}
					if (in_array($value, $activecolumns)) {
						$columns['value'] = true;
					} else {
						$columns['value'] = false;
					}
					$columns['columname'] = $value;
					$columns['column'] = $value;
					$columns['heading'] = $key;
					$c['formdata'] = $columns;
					$elements[] = $c;
				}
			}
			$i++;
		}
	}
	$return['availablecolumns'] = $elements;
	if (!empty($components['calculations']['elements'])) {
		foreach ($components['calculations']['elements'] as $k => $ocalc) {
			$ocalc = (array) $ocalc;
			$calcpluginname[] = $ocalc['pluginname'];
		}
	} else {
		$components['calculations']['elements'] = array();
		$calcpluginname = array();
	}
	$return['calcpluginname'] = $calcpluginname;
	$return['calccolumns'] = $components['calculations']['elements'];
	//exports
	$exporttypes = array();
	if ($reportclass->exports) {
		$exporttypes = array('pdf', 'csv', 'xls', 'ods');
	}
	$exportlists = array();
	foreach ($exporttypes as $key => $exporttype) {
		$list = array();
		$list['name'] = $exporttype;
		if (in_array($exporttype, explode(',', $report->export))) {
			$list['value'] = true;
		} else {
			$list['value'] = false;
		}
		$exportlists[] = $list;
	}
	$return['exportlist'] = $exportlists;
	break;
case 'sendreportemail':
	$args = new stdClass();
	$args->reportid = $reportid;
	$args->jsonformdata = $jsonformdata;

	$return = block_learnerscript_sendreportemail_ajaxform($args);
	break;
case 'tabsposition':
	$report = $DB->get_record('block_learnerscript', array('id' => $reportid));
	$components = (new ls)->cr_unserialize($report->components);
	$elements = isset($components[$component]['elements']) ? $components[$component]['elements'] : array();
	$sortedelements = explode(',', $elementsorder);
	$finalelements = array();
	foreach ($elements as $k => $element) {
		$position = array_search($element['id'], $sortedelements);
		$finalelements[$position] = $element;
	}
	ksort($finalelements);
	$components[$component]['elements'] = $finalelements;
	$finalcomponents = (new ls)->cr_serialize($components);
	$report->components = $finalcomponents;
	$DB->update_record('block_learnerscript', $report);
	break;
}

$json = json_encode($return, JSON_NUMERIC_CHECK);
if ($json) {
	echo $json;
} else {
	echo json_last_error_msg();
}
