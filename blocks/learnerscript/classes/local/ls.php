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
 * A Moodle block for creating LearnerScript
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
namespace block_learnerscript\local;
require_once($CFG->dirroot . '/lib/evalmath/evalmath.class.php');
require_once($CFG->dirroot . "/course/lib.php");
use stdclass;
use DateTime;
use DateTimeZone;
use core_date;
use context_system;
use context_course;
use context_module;
use coursecat;
use highreports;
use block_learnerscript\local\schedule;
use EvalMath;

define('DAILY', 1);
define('WEEKLY', 2);
define('MONTHLY', 3);
define('ONDEMAND', -1);

define('OPENSANS', 1);
define('PTSANS', 2);
/**
 * [urlencode_recursive description]
 * @param  [type] $var [description]
 * @return [type]      [description]
 */
class ls {
	/**
	 * [add_report description]
	 * @param [type] $data    [description]
	 * @param [type] $context [description]
	 */
	public function add_report($data, $context){
		global $CFG,$DB;
		if (!$lastid = $DB->insert_record('block_learnerscript', $data)) {
			print_error('errorsavingreport', 'block_learnerscript');
		} else {
			$event = \block_learnerscript\event\create_report::create(array(
			    'objectid' => $lastid,
			    'context' => $context
			));
			$event->trigger();
			$data->id = $lastid;
			if(in_array($data->type, array('sql','statistics'))){
				self::update_report_sql($data);
			}
		}
		return $lastid;
	}
	/**
	 * [update_report description]
	 * @param  [type] $data    [description]
	 * @param  [type] $context [description]
	 * @return [type]          [description]
	 */
	public function update_report($data,$context){
		global $DB;
		$data->global = $data->global ? $data->global : 0;
		$data->disabletable = isset($data->disabletable) ? $data->disabletable : 0;
		if (!$DB->update_record('block_learnerscript', $data)) {
			print_error('errorsavingreport', 'block_learnerscript');
		} else {
	        $event = \block_learnerscript\event\update_report::create(array(
				    'objectid' => $data->id,
				    'context' => $context
				));
			$event->trigger();
			if(in_array($data->type, array('sql','statistics'))){
				self::update_report_sql($data);
			}
		}
	}
	/**
	 * [delete_report description]
	 * @param  [type] $report  [description]
	 * @param  [type] $context [description]
	 * @return [type]          [description]
	 */
	public function delete_report($report,$context){
		global $DB;
		if ($DB->delete_records('block_learnerscript', array('id' => $report->id))) {
			if ($DB->delete_records('block_ls_schedule', array('reportid' => $report->id))) {
				$event = \block_learnerscript\event\delete_report::create(array(
				    'objectid' => $report->id,
				    'context' => $context
				));
				$event->add_record_snapshot('role_assignments', $report);
				$event->trigger();
			}
		}
	}
	/**
	 * [update_report_sql description]
	 * @param  [type] $report [description]
	 * @return [type]         [description]
	 */
	public function update_report_sql($report){
		global $CFG,$DB;
		require_once $CFG->dirroot . '/blocks/learnerscript/reports/' . $report->type . '/report.class.php';
		$reportclassname = 'report_' . $report->type;
		$reportproperties = new stdclass();
		$reportclass = new $reportclassname($report->id, $reportproperties);
		$components = self::cr_unserialize($reportclass->config->components);
	    $components['customsql']['config'] = $report;
	    $reportclass->config->components = (new ls)->cr_serialize($components);
	    $DB->update_record('block_learnerscript', $reportclass->config);
	}
	/**
	 * [generate_report_plot description]
	 * @param  [type] $reportclass   [description]
	 * @param  [type] $graphdata     [description]
	 * @param  [type] $blockinstance [description]
	 * @return [type]                [description]
	 */
	public function generate_report_plot($reportclass, $graphdata, $blockinstance = null) {
			global $CFG, $PAGE;
			$components = (new ls)->cr_unserialize($reportclass->config->components);
			$seriesvalues = (isset($components['plot']['elements'])) ? $components['plot']['elements'] : array();
			require_once($CFG->dirroot . '/blocks/learnerscript/components/highcharts/graphicalreport.php');
			$highcharts = new highreports();
			if (!empty($seriesvalues)) {
				$series = array();
				switch ($graphdata['pluginname']) {
				case 'pie':
					return $highcharts->piechart($reportclass->finalreport->table->data, $graphdata, $reportclass->config,null, $reportclass->finalreport->table->head);
					break;
				case 'line':
					return $highcharts->lbchart($reportclass->finalreport->table->data, $graphdata, $reportclass->config, 'line', $blockinstanceid, $reportclass->finalreport->table->head);
					break;
				case 'bar':
					return $highcharts->lbchart($reportclass->finalreport->table->data, $graphdata, $reportclass->config, 'bar', $blockinstanceid, $reportclass->finalreport->table->head);
					break;
				case 'column':
					return $highcharts->lbchart($reportclass->finalreport->table->data, $graphdata, $reportclass->config, 'column', $blockinstanceid, $reportclass->finalreport->table->head);
					break;
				case 'combination':
					return $highcharts->combination_chart($reportclass->finalreport->table->data, $graphdata, $reportclass->config, 'combo', $blockinstanceid, $reportclass->finalreport->table->head, $seriesvalues);
					break;
				case 'worldmap':
					return $highcharts->worldmap($reportclass->finalreport->table->data, $graphdata, $reportclass->config,null, $reportclass->finalreport->table->head);
					break;
				case 'treemap':
					return $highcharts->treemap($reportclass->finalreport->table->data, $graphdata, $reportclass->config,null, $reportclass->finalreport->table->head);
				break;
				}
			}
			return true;
		}
		/**
		 * [get_components_data description]
		 * @param  [type] $reportid  [description]
		 * @param  [type] $component [description]
		 * @return [type]            [description]
		 */
		public function get_components_data($reportid, $component) {
			global $CFG, $DB;

			if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
				print_error(get_string('noreportexists', 'block_learnerscript'));
			}
			$elements = (new ls)->cr_unserialize($report->components);
			$elements = isset($elements[$component]['elements']) ? $elements[$component]['elements'] : array();

			require_once $CFG->dirroot . '/blocks/learnerscript/components/' . $component . '/component.class.php';
			$componentclassname = 'component_' . $component;
			$compclass = new $componentclassname($report->id);
			if ($compclass->plugins) {
				$currentplugins = array();
				if ($elements) {
					foreach ($elements as $e) {
						$currentplugins[] = $e['pluginname'];
					}
				}
				$plugins = get_list_of_plugins('blocks/learnerscript/components/' . $component);
				$optionsplugins = array();
				foreach ($plugins as $p) {
					require_once $CFG->dirroot . '/blocks/learnerscript/components/' . $component . '/' . $p . '/plugin.class.php';
					$pluginclassname = 'plugin_' . $p;
					$pluginclass = new $pluginclassname($report);
					if (in_array($report->type, $pluginclass->reporttypes)) {
						if ($pluginclass->unique && in_array($p, $currentplugins)) {
							continue;
						}
						$optionsplugins[] = array('shortname'=>$p,'fullname'=>ucfirst($p));
					}
				}
				asort($optionsplugins);
			}
			return $optionsplugins;
		}
		/**
		 * [report_tabledata description]
		 * @param  [type] $table [description]
		 * @return [type]        [description]
		 */
		public function report_tabledata($table) {
		global $COURSE, $PAGE, $OUTPUT;
			if (isset($table->align)) {
				foreach ($table->align as $key => $aa) {
					if ($aa) {
						$align[$key] = ' text-align:' . fix_align_rtl($aa) . ';'; // Fix for RTL languages
					} else {
						$align[$key] = '';
					}
				}
			}
			if (isset($table->size)) {
				foreach ($table->size as $key => $ss) {
					if ($ss) {
						$size[$key] = ' width:' . $ss . ';';
					} else {
						$size[$key] = '';
					}
				}
			}
			if (isset($table->wrap)) {
				foreach ($table->wrap as $key => $ww) {
					if ($ww) {
						$wrap[$key] = ($ww == 'wrap') ? 'word-break:break-all;' : 'word-break:normal;';
					} else {
						$wrap[$key] = 'word-break:normal;';
					}
				}
			}
			if (empty($table->width)) {
				$table->width = '100%';
			}

			if (empty($table->tablealign)) {
				$table->tablealign = 'center';
			}

			if (!isset($table->cellpadding)) {
				$table->cellpadding = '5';
			}

			if (!isset($table->cellspacing)) {
				$table->cellspacing = '1';
			}

			if (empty($table->class)) {
				$table->class = 'generaltable';
			}

			$tableid = empty($table->id) ? '' : 'id="' . $table->id . '"';
			$countcols = 0;
			$isuserid = -1;
			$countcols = count($table->head);
			$keys = array_keys($table->head);
			$lastkey = end($keys);
			$tableheadkeys = array_keys($table->head);
			foreach ($table->head as $key => $heading) {
				$k = array_search($key, $tableheadkeys);
				$size[$key] = isset($size[$k]) ? $size[$k] : null;
				$wrap[$key] = isset($wrap[$k]) ? $wrap[$k] : 'word-break:normal;';
				$align[$key] = isset($align[$k]) ? $align[$k] : null;
				$tablehead[] = array('key'=>$key,
									 'heading'=>$heading,
									 'size' => $size[$k],
									 'wrap' => $wrap[$k],
									 'align' => $align[$k]);
			}
			$tableproperties = array('width' => $table->width,
								    // 'summary' => $table->summary,
								    'tablealign' => $table->tablealign,
								    'cellpadding' => $table->cellpadding,
								    'cellspacing' => $table->cellspacing,
								    'class' => $table->class);

			return compact('tablehead','tableproperties');
	}
	/**
	 * [urlencode_recursive description]
	 * @param  [type] $var [description]
	 * @return [type]      [description]
	 */
	public function urlencode_recursive($var) {
		if (is_object($var)) {
			$new_var = new stdClass();
			$properties = get_object_vars($var);
			foreach ($properties as $property => $value) {
				$new_var->$property = (new self)->urlencode_recursive($value);
			}
		} else if (is_array($var)) {
			$new_var = array();
			foreach ($var as $property => $value) {
				$new_var[$property] = (new self)->urlencode_recursive($value);
			}
		} else if (is_string($var)) {
			$new_var = urlencode($var);
		} else {
			// nulls, integers, etc.
			$new_var = $var;
		}

		return $new_var;
	}
	/**
	 * [urldecode_recursive description]
	 * @param  [type] $var [description]
	 * @return [type]      [description]
	 */
	public function urldecode_recursive($var) {
		if (is_object($var)) {
			$new_var = new stdClass();
			$properties = get_object_vars($var);
			foreach ($properties as $property => $value) {
				$new_var->$property = self::urldecode_recursive($value);
			}
		} else if (is_array($var)) {
			$new_var = array();
			foreach ($var as $property => $value) {
				$new_var[$property] = self::urldecode_recursive($value);
			}
		} else if (is_string($var)) {
			$new_var = urldecode($var);
		} else {
			$new_var = $var;
		}

		return $new_var;
	}
	/**
	 * [cr_get_my_reports description]
	 * @param  [type]  $courseid   [description]
	 * @param  [type]  $userid     [description]
	 * @param  boolean $allcourses [description]
	 * @return [type]              [description]
	 */
	public function cr_get_my_reports($courseid, $userid, $allcourses = true) {
		global $DB;

		$reports = array();
		if ($courseid == SITEID) {
			$context = context_system::instance();
		} else {
			$context = context_course::instance($courseid);
		}
		if (has_capability('block/learnerscript:managereports', $context, $userid)) {
			if ($courseid == SITEID && $allcourses) {
				$reports = $DB->get_records('block_learnerscript', null, 'name ASC');
			} else {
				$reports = $DB->get_records('block_learnerscript', array('courseid' => $courseid), 'name ASC');
			}

		} else {
			$reports = $DB->get_records_select('block_learnerscript', 'ownerid = ? AND courseid = ? ORDER BY name ASC', array($userid, $courseid));
		}
		return $reports;
	}
	/**
	 * [cr_serialize description]
	 * @param  [type] $var [description]
	 * @return [type]      [description]
	 */
	public function cr_serialize($var) {
		return serialize((new self)->urlencode_recursive($var));
	}
	/**
	 * [cr_unserialize description]
	 * @param  [type] $var [description]
	 * @return [type]      [description]
	 */
	public function cr_unserialize($var) {
		return (new self)->urldecode_recursive(unserialize($var));
	}
	/**
	 * [cr_check_report_permissions description]
	 * @param  [type] $report  [description]
	 * @param  [type] $userid  [description]
	 * @param  [type] $context [description]
	 * @return [type]          [description]
	 */
	public function cr_check_report_permissions($report, $userid, $context) {
		global $DB, $CFG;

		require_once($CFG->dirroot . '/blocks/learnerscript/reports/' . $report->type . '/report.class.php');
		$properties = new stdClass();
		$properties->courseid = $report->id;
		$properties->start = 0;
		$properties->length = 1;
		$properties->search = '';
		$properties->ls_startdate = 0;
		$properties->ls_enddate = time();
		$properties->filters = array();
		$classn = 'report_' . $report->type;
		$classi = new $classn($report->id, $properties);
		return $classi->check_permissions($userid, $context);
	}
	/**
	 * [cr_get_report_plugins description]
	 * @param  [type] $courseid [description]
	 * @return [type]           [description]
	 */
	public function cr_get_report_plugins($courseid) {
		$pluginoptions = array();
		$context = ($courseid == SITEID) ? context_system::instance() : context_course::instance($courseid);
		$plugins = get_list_of_plugins('blocks/learnerscript/reports');
		if ($plugins) {
			foreach ($plugins as $p) {
				if ($p == 'sql' && !has_capability('block/learnerscript:managesqlreports', $context)) {
					continue;
				}

				$pluginoptions[$p] = get_string('report_' . $p, 'block_learnerscript');
			}
		}
		return $pluginoptions;
	}
	/**
	 * [cr_get_export_plugins description]
	 * @return [type] [description]
	 */
	public function cr_get_export_plugins() {
		$exportoptions = array();
		$plugins = get_list_of_plugins('blocks/learnerscript/export');
		if ($plugins) {
			foreach ($plugins as $p) {
				$pluginoptions[$p] = get_string('export_' . $p, 'block_learnerscript');
			}
		}
		return $pluginoptions;
	}
	/**
	 * [cr_get_export_options description]
	 * @param  [type] $reportid [description]
	 * @return [type]           [description]
	 */
	public function cr_get_export_options($reportid) {
		global $DB;
		$reportconfig = $DB->get_record('block_learnerscript', array('id' => $reportid));
		if ($reportconfig->export) {
			$export_options = array_filter(explode(',', $reportconfig->export));
		} else {
			$export_options = false;
		}
		return $export_options;
	}
	/**
	 * [table_to_excel description]
	 * @param  [type] $filename [description]
	 * @param  [type] $table    [description]
	 * @return [type]           [description]
	 */
	public function table_to_excel($filename, $table) {
		global $DB, $CFG;
		require_once $CFG->dirroot . '/lib/excellib.class.php';

		if (!empty($table->head)) {
			$countcols = count($table->head);
			$keys = array_keys($table->head);
			$lastkey = end($keys);
			foreach ($table->head as $key => $heading) {
				$matrix[0][$key] = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($heading))));
			}
		}

		if (!empty($table->data)) {
			foreach ($table->data as $rkey => $row) {
				foreach ($row as $key => $item) {
					$matrix[$rkey + 1][$key] = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($item))));
				}
			}
		}

		$downloadfilename = clean_filename($filename);
		/// Creating a workbook
		$workbook = new MoodleExcelWorkbook("-");
		/// Sending HTTP headers
		$workbook->send($downloadfilename);
		/// Adding the worksheet
		$myxls = &$workbook->add_worksheet($filename);

		foreach ($matrix as $ri => $col) {
			foreach ($col as $ci => $cv) {
				$myxls->write_string($ri, $ci, $cv);
			}
		}

		$workbook->close();
		exit;
	}

	/**
	 * Returns contexts in deprecated and current modes
	 *
	 * @param  int $context The context
	 * @param  int $id      The context id
	 * @param  int $flags   The flags to be used
	 * @return stdClass     An object instance
	 */
	public function cr_get_context($context, $id = null, $flags = null) {

		if ($context == CONTEXT_SYSTEM) {
			if (class_exists('context_system')) {
				return context_system::instance();
			} else {
				return get_context_instance(CONTEXT_SYSTEM);
			}
		} else if ($context == CONTEXT_COURSE) {
			if (class_exists('context_course')) {
				return context_course::instance($id, $flags);
			} else {
				return get_context_instance($context, $id, $flags);
			}
		} else if ($context == CONTEXT_COURSECAT) {
			if (class_exists('context_coursecat')) {
				return context_coursecat::instance($id, $flags);
			} else {
				return get_context_instance($context, $id, $flags);
			}
		} else if ($context == CONTEXT_BLOCK) {
			if (class_exists('context_block')) {
				return context_block::instance($id, $flags);
			} else {
				return get_context_instance($context, $id, $flags);
			}
		} else if ($context == CONTEXT_MODULE) {
			if (class_exists('context_module')) {
				return get_context_instance::instance($id, $flags);
			} else {
				return get_context_instance($context, $id, $flags);
			}
		} else if ($context == CONTEXT_USER) {
			if (class_exists('context_user')) {
				return context_user::instance($id, $flags);
			} else {
				return get_context_instance($context, $id, $flags);
			}
		}

		return get_context_instance($context, $id, $flags);
	}
	/**
	 * [cr_make_categories_list description]
	 * @param  [type]  &$list              [description]
	 * @param  [type]  &$parents           [description]
	 * @param  string  $requiredcapability [description]
	 * @param  integer $excludeid          [description]
	 * @param  [type]  $category           [description]
	 * @param  string  $path               [description]
	 * @return [type]                      [description]
	 */
	public function cr_make_categories_list(&$list, &$parents, $requiredcapability = '', $excludeid = 0, $category = NULL, $path = "") {
		global $CFG, $DB;
		require_once $CFG->libdir . '/coursecatlib.php';

		// For categories list use just this one public function:
		if (empty($list)) {
			$list = array();
		}
		$list += coursecat::make_categories_list($requiredcapability, $excludeid);

		// Building the list of all parents of all categories in the system is highly undesirable and hardly ever needed.
		// Usually user needs only parents for one particular category, in which case should be used:
		// coursecat::get($categoryid)->get_parents()
		if (empty($parents)) {
			$parents = array();
		}
		$all = $DB->get_records_sql('SELECT id, parent FROM {course_categories} ORDER BY sortorder');
		foreach ($all as $record) {
			if ($record->parent) {
				$parents[$record->id] = array_merge($parents[$record->parent], array($record->parent));
			} else {
				$parents[$record->id] = array();
			}
		}
	}
	/**
	 * [cr_import_xml description]
	 * @param  [type]  $xml        [description]
	 * @param  [type]  $course     [description]
	 * @param  boolean $timeprefix [description]
	 * @return [type]              [description]
	 */
	public function cr_import_xml($xml, $course, $timeprefix = true, $config = false) {
		global $CFG, $DB, $USER, $PAGE;
		$context = context_system::instance();
		require_once($CFG->dirroot . '/lib/xmlize.php');
		$data = xmlize($xml, 1, 'UTF-8');
		if (isset($data['report']['@']['version'])) {
			$newreport = new stdclass;
			foreach ($data['report']['#'] as $key => $val) {
				if ($key == 'components') {
					$val[0]['#'] = base64_decode(trim($val[0]['#']));
					// fix url_encode " and ' when importing SQL queries
					$temp_components = (new self)->cr_unserialize($val[0]['#']);
					$temp_components['customsql']['config']->querysql = str_replace("\'", "'", $temp_components['customsql']['config']->querysql);
					$temp_components['customsql']['config']->querysql = str_replace('\"', '"', $temp_components['customsql']['config']->querysql);
					$val[0]['#'] = (new self)->cr_serialize($temp_components);
				}
				$newreport->{$key} = trim($val[0]['#']);
			}
			$newreport->courseid = $course->id;
			$newreport->ownerid = $USER->id;
			if ($timeprefix) {
				$newreport->name .= " (" . userdate(time()) . ")";
			}
			try {
				$reportid = $DB->insert_record('block_learnerscript', $newreport);

				$event = \block_learnerscript\event\create_report::create(array(
				    'objectid' => $reportid,
				    'type' => $newreport->type,
				    'context' => $context
				));
				$event->trigger();
				if($config && $reportid)  {
					$PAGE->set_context($context);
					$regions = array('side-db-first', 'side-db-second', 'side-db-third',
                 'side-db-four', 'side-db-one', 'side-db-two',
                 'side-db-three', 'side-db-main', 'center-first', 'center-second','reports-db-one','reports-db-two',
                 'reportdb-one','reportdb-second','reportdb-third','first-maindb');
					$PAGE->blocks->add_regions($regions);
					$blocksinstancedata = $data['report']['#']['instance'];
					$blockspositiondata = $data['report']['#']['position'];
					if(!empty($blocksinstancedata)) {
						foreach($blocksinstancedata as $k => $blockinstancedata) {
							if (isset($blockinstancedata['@']['version'])) {
								$blockinstance = new stdClass();
								foreach ($blockinstancedata['#'] as $key => $val) {
									$blockinstance->{$key} = trim($val[0]['#']);
								}
                                $blockexists = $PAGE->blocks->is_known_block_type($blockinstance->blockname, true);
                                if ($blockexists) {
                                    $blockconfig = new stdClass();
                                    $blockconfig->title = $blockinstance->title;
                                    $blockconfig->reportlist = $reportid;
                                    $blockconfig->reportcontenttype = $blockinstance->reportcontenttype;
                                    $blockconfig->reporttype = $blockinstance->reporttype;
                                    $blockconfig->logo = $blockinstance->logo;
                                    $blockconfig->tilescolourpicker = $blockinstance->tilescolourpicker;
                                    if ($blockinstance->blockname == 'reporttiles') {
                                    	$blockconfig->tileformat = $blockinstance->tileformat;
                                    } else if ($blockinstance->blockname == 'reportdashboard') {$blockconfig->disableheader = $blockinstance->disableheader;
                                    }
                                    $blockconfig->tilescolour = $blockinstance->tilescolour;
                                    $blockconfig->url = $blockinstance->url;
                                    $configdata = base64_encode(serialize($blockconfig));
                                    $PAGE->blocks->add_block($blockinstance->blockname, $blockinstance->defaultregion, $blockinstance->defaultweight, false, $blockinstance->pagetypepattern,
                                    	$blockinstance->subpagepattern);
                                    $lastblockinstanceid = $DB->get_field_sql("SELECT id from {block_instances} where blockname = '$blockinstance->blockname' order by id DESC", array(), IGNORE_MULTIPLE);
                                    $DB->set_field('block_instances', 'configdata', $configdata, array('id' => $lastblockinstanceid));
                                    if($lastblockinstanceid) {
                                        if (isset($blockspositiondata[$k]['@']['version'])) {
                                            if(!empty($blockspositiondata[$k]['#'])) {
                                                $blockposition = new stdClass();
                                                $blockposition->blockinstanceid = $lastblockinstanceid;
                                                foreach ($blockspositiondata[$k]['#'] as $key => $val) {
                                                    $blockposition->{$key} = trim($val[0]['#']);
                                                }
                                                $DB->insert_record('block_positions', $blockposition);
                                            }
                                        }
                                    }
                                }
				            }
				        }
		        	}
				}
			} catch (dml_exception $ex) {
				return false;
			}
			return $reportid;
		}
		return false;
	}

	// For avoid warnings in versions minor than 2.7
	/**
	 * [cr_add_to_log description]
	 * @param  [type]  $courseid [description]
	 * @param  [type]  $module   [description]
	 * @param  [type]  $action   [description]
	 * @param  string  $url      [description]
	 * @param  string  $info     [description]
	 * @param  integer $cm       [description]
	 * @param  integer $user     [description]
	 * @return [type]            [description]
	 */
	public function cr_add_to_log($courseid, $module, $action, $url = '', $info = '', $cm = 0, $user = 0) {
		global $CFG;

		if ($CFG->version < 2014051200) {
			add_to_log($courseid, $module, $action, $url, $info, $cm, $user);
		}
	}
	/**
	 * [cr_get_reportinstance description]
	 * @param  [type] $reportid [description]
	 * @return [type]           [description]
	 */
	public function cr_get_reportinstance($reportid) {
		global $DB;
		if (!$report = $DB->get_record('block_learnerscript', array('id' => $reportid))) {
			print_error('reportdoesnotexists', 'block_learnerscript');
		}
		return $report;
	}
	/**
	 * [create_reportclass description]
	 * @param  [type]  $reportid         [description]
	 * @param  boolean $reportproperties [description]
	 * @return [type]                    [description]
	 */
	public function create_reportclass($reportid, $reportproperties = false) {
		global $CFG, $DB;
		$report = (new self)->cr_get_reportinstance($reportid);
		require_once $CFG->dirroot . '/blocks/learnerscript/reports/' . $report->type . '/report.class.php';
		$reportclassname = 'report_' . $report->type;
		$reportclass = new $reportclassname($report, $reportproperties);
		if($reportproperties){
			isset($reportproperties->courseid) ? $reportclass->courseid = $reportproperties->courseid: null;
			isset($reportproperties->ls_startdate) ? $reportclass->ls_startdate = $reportproperties->ls_startdate: null;
			isset($reportproperties->ls_enddate) ? $reportclass->ls_enddate = $reportproperties->ls_enddate: null;
		}
		return $reportclass;
	}
	/**
	 * [cr_listof_reporttypes description]
	 * @param  [type]  $reportid       [description]
	 * @param  boolean $checktable     [description]
	 * @param  boolean $component_data [description]
	 * @return [type]                  [description]
	 */
	public function cr_listof_reporttypes($reportid, $checktable = true, $component_data = true) {
		global $DB;
		// $reportclass = (new self)->create_reportclass($reportid);
		$reportcomponents = $DB->get_field('block_learnerscript', 'components', array('id' => $reportid));
		$components = (new self)->cr_unserialize($reportcomponents);

		$reportcontenttypes = array();
		if (isset($components['plot'])) {
			foreach ($components['plot']['elements'] as $key => $value) {
				if(isset($value['formdata'])){
					if($component_data) {
						$reportcontenttypes[$value['id']] = ucfirst($value['formdata']->chartname);
					} else {
						$reportcontenttypes[] = array('pluginname' => $value['pluginname'], 'chartname' => ucfirst($value['formdata']->chartname),'chartid'=>$value['id'], 'title' => get_string($value['pluginname'], 'block_learnerscript'));
					}
				}
			}
		}
		$disablereporttable = $DB->get_field('block_learnerscript',  'disabletable',  array('id' => $reportid));

		if($checktable) {
			if($component_data) {
				$reportcontenttypes['table'] = get_string('table', 'block_learnerscript');
			}else{
				if($disablereporttable == 0){
					$reportcontenttypes[] = array('chartid'=>'table','chartname'=>get_string('table', 'block_learnerscript'));
				}
			}
		}
		return $reportcontenttypes;
	}
	/**
	 * [add_customreports_sql description]
	 * @param [type] $reports [description]
	 */
	public function add_customreports_sql($reports) {
		global $DB, $CFG;
		foreach ($reports as $report) {
			$importurl = urldecode($CFG->wwwroot . '/blocks/learnerscript/reports/sql/customreports/' . $report . '.xml');
			$fcontents = file_get_contents($importurl);
			$course = $DB->get_record("course", array("id" => SITEID));
			if (cr_import_xml($fcontents, $course, false)) {
				//return true;
				//redirect("$CFG->wwwroot/blocks/learnerscript/managereport.php?courseid={$course->id}", get_string('reportcreated', 'block_learnerscript'));
			} else {
				print_error(get_string('errorimporting', 'block_learnerscript'));
			}
		}
	}
	/**
	 * List of scheduled reports data
	 * @param  [Int] $frequency
	 * @return Object       List of scheduled reports
	 */
	public function schedulereportsquery($frequency = false) {
		global $DB;
		core_date::set_default_server_timezone();
		$now = new DateTime("now", core_date::get_server_timezone_object());
		$date = $now->format('Y-m-d');
		$hour = $now->format('H');
		$frequencyquery = '';
		if ($frequency == ONDEMAND) {
			$frequencyquery = " AND crs.frequency = $frequency AND crs.timemodified = 0 ";
		} else {
			$frequencyquery = " AND DATE(FROM_UNIXTIME(crs.nextschedule)) = '$date' AND HOUR(FROM_UNIXTIME(crs.nextschedule)) = $hour";
		}

		$sql = "SELECT crs.*, cr.name, cr.courseid, u.timezone
	              FROM {block_ls_schedule} as crs
	              JOIN {block_learnerscript} as cr ON crs.reportid = cr.id
	              JOIN {user} as u ON crs.userid = u.id
	             WHERE u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 $frequencyquery";
		$scheduledreports = $DB->get_records_sql($sql);
		return $scheduledreports;
	}
	/**
	 * Processing scheduling reports cron based on frequency
	 * @param  Integer $frequency DAILY/WEEKLY/MONTHLY const values
	 * @return  boolean
	 */
	public function process_scheduled_reports($frequency = false) {
		global $CFG, $DB;

		$schedule = new \block_learnerscript\local\schedule;
		$scheduledreports = (new self)->schedulereportsquery($frequency);
		$totalschedulereports = count($scheduledreports);
		mtrace('Processing ' . $totalschedulereports . ' scheduled reports');
		if ($totalschedulereports > 0) {
			foreach ($scheduledreports as $scheduled) {
				switch ($scheduled->exporttofilesystem) {
					case REPORT_EXPORT_AND_EMAIL:
						mtrace('ReportID (' . $scheduled->reportid . ') - ScheduleID (' . $scheduled->id . ') Option: Email and save scheduled report to file.');
						break;
					case REPORT_EXPORT:
						mtrace('ReportID (' . $scheduled->reportid . ') - ScheduleID (' . $scheduled->id . ') Option: Save scheduled report to file system only.');
						break;
					case REPORT_EMAIL:
						mtrace('ReportID (' . $scheduled->reportid . ') - ScheduleID (' . $scheduled->id . ') Option: Email scheduled report.');
						break;
				}
				$schedule->scheduledreport_send_scheduled_report($scheduled);
				if ($scheduled->frequency == DAILY) {
					$scheduletype = 'dailyreport';
				} else if ($scheduled->frequency == WEEKLY) {
					$scheduletype = 'weeklyreport';
				} else if ($scheduled->frequency == MONTHLY) {
					$scheduletype = 'monthlyreport';
				} else if ($scheduled->frequency == ONDEMAND) {
					$scheduletype = 'On Demand';
				} else {
					$scheduletype = 'N/A';
				}

				if ($frequency != ONDEMAND) {
					$scheduled->nextschedule = $schedule->next($scheduled);
					$scheduled->timemodified = time();
					if (!$DB->update_record('block_ls_schedule', $scheduled)) {
						mtrace('Failed to update next report field for scheduled report id:' . $scheduled->id);
					}
				}
			}
		}
		return true;
	}
	/**
	 * Scheduled reports cron
	 * @return boolean true/false
	 */
	public function initiate_scheduled_reports() {
		(new self)->process_scheduled_reports();
	}
	/**
	 * PDF Report Export Header
	 * @return Report Header
	 */
	public function pdf_reportheader() {
		$reportlogo = get_config('block_learnerscript', 'logo');

		$headerimgpath = get_reportheader_imagepath();
		if (@getimagesize($headerimgpath)) {
			$headerimgpath = $headerimgpath;
		}
		if ($headerimgpath) {
			$reportlogoimage = '<img src="' . $headerimgpath . '" alt=' . get_string("altreportimage", "block_learnerscript") . ' height="80px">';
		} else {
			$reportlogoimage = "";
		}
		return $reportlogoimage;
	}
	/**Dynamic Columns For Sections */
	/**
	 * [learnerscript_sections_dynamic_columns description]
	 * @param  [type] $columns [description]
	 * @param  [type] $config  [description]
	 * @return [type]          [description]
	 */
	public function learnerscript_sections_dynamic_columns($columns, $config, $basicparams){
		global $CFG;

		foreach ($columns as $colvalue) {
	        $colvalue = (object)$colvalue;
	        if ($colvalue->pluginname == "topicwiseperformance") {
	            require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/topicwiseperformance/plugin.class.php');
	            $pluginname = $colvalue->pluginname;
	            $plgname = 'plugin_topicwiseperformance';
	            $dynclass = new $plgname($config, $colvalue);
	            $flag = 1;
	        }
	    }
	    if ($flag == 1) {
	        $reportinfo = $dynclass->report;
	        if ($reportinfo->type == 'topic_wise_performance') {
	            $filter_courses = optional_param('filter_courses', 0, PARAM_INT);
	            $courseid = isset( $basicparams['filter_courses']) ? $basicparams['filter_courses'] : null;
	            if (!empty($filter_courses)) {
	            	$courseid = $filter_courses;
	            }
	            // $qlist = $dynclass->get_sessionslist($courseid);
	            if($courseid > 1) {
			        $format = \course_get_format($courseid);
			        $modinfo = \get_fast_modinfo($courseid);
			        $modules = $modinfo->get_used_module_names();
			        $sections = array();
			        if ($format->uses_sections()) {
			            foreach ($modinfo->get_section_info_all() as $section) {
			                if ($section->uservisible) {
			                    $sections[] = $format->get_section_name($section);
			                }
			            }
			        }
			    }
	        }
	        if (!empty($sections)) {
		        foreach($sections as $k => $value){
		            $columns[] = (new self)->learnerscript_create_dynamic_sectioncolumns($value,
		            	'topicwiseperformance', $k);
		        }
		    }
	    }
		return $columns;
	}
	/**
	 * [learnerscript_create_dynamic_sectioncolumns description]
	 * @param  [type] $value     [description]
	 * @param  [type] $columname [description]
	 * @return [type]            [description]
	 */
	public function learnerscript_create_dynamic_sectioncolumns($value, $columname, $k){
		$newcolumn = array();
		$formdata = new stdclass();
		$formdata->column = "section$k";
		$formdata->columname = $value;;
		$newcolumn['formdata'] = $formdata;
		$newcolumn['pluginname'] = $columname;
		$newcolumn['pluginfullname'] = $columname;
		return $newcolumn;
	}

	/**Dynamic Columns For Course users */
	/**
	 * [learnerscript_courseusers_dynamic_columns description]
	 * @param  [type] $columns [description]
	 * @param  [type] $config  [description]
	 * @return [type]          [description]
	 */
	public function learnerscript_courseusers_dynamic_columns($columns, $config){
		global $DB, $CFG;

		foreach($columns as $colvalue){
	        $colvalue =(object)$colvalue;
	        if($colvalue->pluginname=="courseactivitiesinfo"){
	            require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/courseactivitiesinfo/plugin.class.php');
	            $pluginname =$colvalue->pluginname;
	            $plgname= 'plugin_courseactivitiesinfo';
	            $dynclass = new $plgname($config, $colvalue);
	            $flag=1;
	        }
	    }
	    if($flag==1){
	        $reportinfo= $dynclass->report;
	        if($reportinfo->type=='courseactivitiesinfo'){
	            $filter_courses = optional_param('filter_courses', 0, PARAM_INT);
	            $courseid = optional_param('courseid', $filter_courses, PARAM_INT);
	            if(!empty($filter_courses)){
	            	$courseid = $filter_courses;
	            }
	            $coursecontext = context_course::instance($courseid);
	            $studentroleid = $DB->get_field('role','id',array('shortname'=>'student'));
	            $qlist = get_role_users($studentroleid, $coursecontext);
	            $sql ="SELECT u.id, u.lastname, u.firstname,c.id AS courseid
						FROM {user} u
						JOIN {role_assignments} ra ON ra.userid = u.id
						JOIN {context} ct ON ct.id = ra.contextid
						JOIN {course} c ON c.id = ct.instanceid
						JOIN {role} r ON r.id = ra.roleid
						WHERE r.id = $studentroleid AND c.id = $filter_courses AND u.id IN (3,4)";
				$qlist = $DB->get_records_sql($sql);
	        }
	        foreach($qlist as $value){
	            $columns[]=learnerscript_create_dynamic_usergradecolumns($value,'courseactivitiesinfo');
	            $columns[]=learnerscript_create_dynamic_userstatuscolumns($value,'courseactivitiesinfo');
	        }
	    }
		return $columns;
	}
	/**
	 * [learnerscript_create_dynamic_usergradecolumns description]
	 * @param  [type] $value     [description]
	 * @param  [type] $columname [description]
	 * @return [type]            [description]
	 */
	public function learnerscript_create_dynamic_usergradecolumns($value, $columname){
		global $DB;
		$newcolumn=array();
		$formdata= new stdclass();
		$formdata->column="grade$value->id";
		$userrecord = $DB->get_record_sql("SELECT * FROM {user} WHERE id = $value->id");
		$formdata->columname='Grade('.$userrecord->firstname.' '.$userrecord->lastname .')';
		$newcolumn['formdata']=$formdata;
		$newcolumn['pluginname']=$columname;
		$newcolumn['pluginfullname']=$columname;
		return $newcolumn;
	}
	/**
	 * [learnerscript_create_dynamic_userstatuscolumns description]
	 * @param  [type] $value     [description]
	 * @param  [type] $columname [description]
	 * @return [type]            [description]
	 */
	public function learnerscript_create_dynamic_userstatuscolumns($value, $columname){
		global $DB;
		$newcolumn=array();
		$formdata= new stdclass();
		$formdata->column="status$value->id";
		$userrecord = $DB->get_record_sql("SELECT * FROM {user} WHERE id = $value->id");
		$formdata->columname='Status('.$userrecord->firstname.' '.$userrecord->lastname .')';
		$newcolumn['formdata']=$formdata;
		$newcolumn['pluginname']=$columname;
		$newcolumn['pluginfullname']=$columname;
		return $newcolumn;
	}
	/**
	 * [report_componentslist description]
	 * @param  [type] $report [description]
	 * @param  [type] $comp   [description]
	 * @return [type]         [description]
	 */
	public function report_componentslist($report,$comp){
		global $CFG;
		require_once($CFG->dirroot.'/blocks/learnerscript/reports/'.$report->type.'/report.class.php');

		$reportclassname = 'report_'.$report->type;
		$properties = new stdClass();
		$reportclass = new $reportclassname($report->id, $properties);

		if(!in_array($comp,$reportclass->components))
			print_error('badcomponent');

		$elements = (new self)->cr_unserialize($report->components);
		$elements = isset($elements[$comp]['elements'])? $elements[$comp]['elements'] : array();

		require_once($CFG->dirroot.'/blocks/learnerscript/components/'.$comp.'/component.class.php');
		$componentclassname = 'component_'.$comp;
		$compclass = new $componentclassname($report->id);
			if($compclass->plugins){
				$currentplugins = array();
				if($elements){
					foreach($elements as $e){
						$currentplugins[] = $e['pluginname'];
					}
				}
				$plugins = get_list_of_plugins('blocks/learnerscript/components/'.$comp);
				$optionsplugins = array();
				foreach($plugins as $p){
					require_once($CFG->dirroot.'/blocks/learnerscript/components/'.$comp.'/'.$p.'/plugin.class.php');
					$pluginclassname = 'plugin_'.$p;
					$pluginclass = new $pluginclassname($report);
					if(in_array($report->type,$pluginclass->reporttypes)){
						if($pluginclass->unique && in_array($p,$currentplugins))
							continue;
						$optionsplugins[$p] = get_string($p,'block_learnerscript');
					}
				}
				asort($optionsplugins);
			}
		return $optionsplugins;
	}
	/**
	 * [column_definations description]
	 * @param  [type] $reportclass [description]
	 * @return [type]              [description]
	 */
	public function column_definations($reportclass){
		$columnDefs = array();
		$datacolumns = array();
		$i = 0;
		$re = array();
		if (!empty($reportclass->finalreport->table->head)) {
			$re = array_diff(array_keys($reportclass->finalreport->table->head), $reportclass->orderable);
		}
		if (!empty($reportclass->finalreport->table->head)) {
			foreach ($reportclass->finalreport->table->head as $key => $value) {
				$datacolumns[]['data'] = $value;
				$columnDef = new stdClass();
				$align = $reportclass->finalreport->table->align[$i] ? $reportclass->finalreport->table->align[$i] : 'left';
			    $wrap = ($reportclass->finalreport->table->wrap[$i] == 'wrap') ? 'break-all' : 'normal';
			    $width = ($reportclass->finalreport->table->size[$i]) ? $reportclass->finalreport->table->size[$i] : '';
			    $columnDef->className = 'dt-body-'. $align;

			    $columnDef->wrap = $wrap;
			    $columnDef->width = $width;
			    $columnDef->targets = $i;
			    if($re[$i]) {
				    $columnDef->orderable = false;
			    } else {
			    	$columnDef->orderable = true;
			    }
			    $i++;
			    $columnDefs[] = $columnDef;
			}
		}
		return compact('datacolumns','columnDefs');
	}
    public function check_rolewise_permission($reportid, $role) {
        global $DB, $CFG, $USER;
        $context = context_system::instance();
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        if ((!is_siteadmin() && has_capability('block/learnerscript:managereports', $context, $USER->id)) || ($role == 'manager')) {
        	return true;
        }
        $reportcomponents = $DB->get_field('block_learnerscript', 'components', array('id' => $reportid));
        $components = (new ls)->cr_unserialize($reportcomponents);
        $permissions = (isset($components['permissions'])) ? $components['permissions'] : array();

		if(empty($permissions['elements'])){
			return false;
		} else {
            foreach ($permissions['elements'] as $p) {
            	if($p['pluginname'] == 'roleincourse') {
            		if($roleid == $p['formdata']->roleid) {
            			return true;
            		}
            	}
            }
            return false;
		}
    }

    public function listofreportsbyrole($coursels = false, $statistics = false, $parentcheck = false, $reportslist = false) {
    	global $DB,$PAGE;
    	//Course context reports
    	if($PAGE->context->contextlevel == 50 || $PAGE->context->contextlevel == 70){
    		$coursels = true;
    	}

    	if($statistics){
			$statisticsreports = array();
			$roles = $DB->get_records_sql("SELECT id, shortname FROM {role}");
			foreach($roles as $role){
				$rolereports = (new ls)->rolewise_statisticsreports($role->shortname);
				foreach($rolereports as $key => $value){
					$statisticsreports[$value] = $value;
				}
			}
    		if(empty($_SESSION['role']) && !empty($statisticsreports)){
				$implodereports = implode(',', $statisticsreports);
    			$reportlist = $DB->get_records_select_menu('block_learnerscript', 'global = 1 AND visible = 1 AND id NOT IN ('.$implodereports.') AND type = "statistics"', null, '', 'id, name');
    		} else{
    			$reportlist = $DB->get_records_select_menu('block_learnerscript', 'global = 1 AND visible = 1 AND type = "statistics"', null, '', 'id, name');
    		}
    	} else{
    		$reportlist = $DB->get_records_select_menu('block_learnerscript', 'global = 1 AND visible = 1 AND type != "statistics"', null, '', 'id, name');
    	}

		$rolereports = array();
		if(!empty($reportlist)) {
			$properties = new stdClass();
			$properties->courseid = SITEID;
			$properties->start = 0;
			$properties->length = 1;
			$properties->search = '';
			foreach ($reportlist as $key => $value) {
				if(!empty($_SESSION['role'])) {
		            $check_rolewise_permission = (new ls)->check_rolewise_permission($key, $_SESSION['role']);
			        if($check_rolewise_permission == false) {
			            continue;
			        }
				}
				$reportcontenttypes = (new ls)->cr_listof_reporttypes($key);
				if(sizeof($reportcontenttypes) < 1 && $coursels){
				    continue;
            	}
	            $report = $this->create_reportclass($key, $properties);
	            if (!$reportslist) {
	            	if($report->parent == false && !$parentcheck && !$coursels) {
	            	continue;
	            }
	            }
	            if($coursels){
	            	if(!$report->courselevel){
	            		continue;
	            	}
	            }
	            $rolereports[] = ['id'=> $key, 'name' => $value];
	        }
	    }
        return $rolereports;
    }
    public function rolewise_statisticsreports($role) {
    	global $DB,$PAGE;
    	if(empty($role) || $role == 'manager'){
    		return array();
    	}
		$reportlist = $DB->get_records_select_menu('block_learnerscript', 'global = 1 AND visible = 1 AND type = "statistics"', null, '', 'id, name');
		$statisticsreports = array();
		if(!empty($reportlist)) {
			foreach ($reportlist as $key => $value) {
				if(!empty($role)) {
		            $check_rolewise_permission = (new ls)->check_rolewise_permission($key, $role);
			        if($check_rolewise_permission == false) {
			            continue;
			        }
				}
	            $statisticsreports[] =$key;
	        }
	    }
        return $statisticsreports;
    }
    function get_reporttitle($reporttype,$reportclassparams){
    	global $DB;
    	$reporttitle = $reporttype;
    	if(array_key_exists('filter_courses',$reportclassparams) && $reporttitle != 'Course profile'){
    		$coursename = $DB->get_field('course','fullname',array('id'=>$reportclassparams['filter_courses']));
    		$reporttitle = str_replace('Course', '<b>'.$coursename.'</b> Course', $reporttype);
    	}
    	if(array_key_exists('filter_status',$reportclassparams) && $reportclassparams['filter_status'] != 'all'){
    		$reporttitle = $reporttitle . ' - ' . '<b>' . get_string($reportclassparams['filter_status'],'block_learnerscript') . '</b>';
    	}
    	if(array_key_exists('filter_users',$reportclassparams)){
    		if(is_int($reportclassparams['filter_users'])){
	    		$learnername = $DB->get_field_sql("select firstname as fullname from {user}
	    											where id=".$reportclassparams['filter_users']."");
	    		$reporttitle = str_replace('Learner', '<b>'.$learnername.'</b>', $reporttitle);
	    		$reporttitle = str_replace('My', '<b>'.$learnername.'\'s</b>', $reporttitle);
    		}
    	}
    	return $reporttitle;
    }
    public function importlsusertours() {
	    global $CFG, $DB;
	    $usertours = $CFG->dirroot . '/blocks/learnerscript/usertours/';
	    $totalusertours = count(glob($usertours . '*.json'));
	    $usertoursjson = glob($usertours . '*.json');
	    $pluginmanager = new \tool_usertours\manager();
	    for ($i = 0; $i < $totalusertours; $i++) {
	        $importurl = $usertoursjson[$i];
	        if (file_exists($usertoursjson[$i])
	                && pathinfo($usertoursjson[$i], PATHINFO_EXTENSION) == 'json') {
	            $data = file_get_contents($importurl);
	            $tourconfig = json_decode($data);
	            $tourexists = $DB->record_exists('tool_usertours_tours', array('name' => $tourconfig->name));
	            if (!$tourexists) {
	                $tour = $pluginmanager->import_tour_from_json($data);
	            }
	        }
	    }
	}
    public function lsconfigreports() {
		global $CFG, $DB;
		$path = $CFG->dirroot . '/blocks/learnerscript/backup/';
		$learnerscriptreports = glob($path . '*.xml');
		$lsreportscount = $DB->count_records('block_learnerscript');
		$lsimportlogssql = "SELECT other
		                      FROM {logstore_standard_log}
		                     WHERE action = :action AND target = :target
		                            AND objecttable = :objecttable AND other <> :other";
		$lsimportlogs = $DB->get_fieldset_sql($lsimportlogssql, array('action' => 'import',
		    'target' => 'report', 'objecttable' => 'block_learnerscript', 'other' => 'N;'));
		$lastreport = 0;
		foreach ($lsimportlogs as $lsimportlog) {
		    $lslog = unserialize($lsimportlog);
		    if ($lslog['status'] == false) {
		        $errorreportsposition[$lslog['position']] = $lslog['position'];
		    }

		    if ($lslog['status'] == true) {
		        $lastreportposition = $lslog['position'];
		    }
		}

		$importstatus = false;
		if (empty($lsimportlogs) || $lsreportscount < 1) {
		    $total = count($learnerscriptreports);
		    $current = 1;
		    $percentwidth = $current / $total * 100;
		    $importstatus = true;
		    $errorreportsposition = array();
		    $lastreportposition = 0;
		} else {
		    $total = 0;
		    foreach ($learnerscriptreports as $position => $learnerscriptreport) {
		        if ((!empty($errorreportsposition) && in_array($position, $errorreportsposition)) || $position >= $lastreportposition) {
		            $total++;
		        }
		    }
		    if (empty($errorreportsposition)) {
		        $current = $lastreportposition + 1;
		        $errorreportsposition = array();
		    } else {
		        $occuredpositions = array_merge($errorreportsposition, array($lastreportposition));
		        $current = min($occuredpositions);
		    }
		    if ($total > 0) {
		        $importstatus = true;
		    }
		}
		$errorreportspositiondata = serialize($errorreportsposition);
		return compact('importstatus', 'total', 'current', 'errorreportspositiondata',
			'lastreportposition');
	}
	/**
     * login user roles (course and system)
     * @return [array] list of roles
     */
    public function get_currentuser_roles($userid = false){
      global $DB, $USER;
      $userid = $userid > 0 ? $userid : $USER->id;
      $rolesql = "SELECT DISTINCT r.id, r.shortname
                   FROM {role} AS r
                   JOIN {role_assignments} AS ra ON ra.roleid = r.id
                   WHERE ra.userid = $userid";
      $roles = $DB->get_records_sql_menu($rolesql);
      ksort($roles);
      return $roles;
    }
    public function learnerscript() {
        global $CFG, $USER, $DB;

        $curl = new \curl;
        $ls = get_config('block_learnerscript', 'serialkey');
        if (empty($ls)) {
            return false;
        }
        $params = array();
        $params['serial'] = $ls;
        $params['surl'] = $CFG->wwwroot;
		$params['level'] = true;
        $param = json_encode($params);

        $json = $curl->post('https://learnerscript.com?wc-api=custom_validate_serial_key', $param);
        $data = (object)json_decode($json);

        if ($data->success == 'true') {
            $status = 1;
        } else {
            $status = 0;
	    set_config('serialkey', null ,'block_learnerscript');
        }
    }
    public function scormtimespent() {
    	global $DB, $USER;
	  	$scormrecord = get_config('block_learnerscript', 'scormtime');
	  	if (empty($scormrecord)) {
	    	$moduleid = $DB->get_field('modules', 'id', array('name' => 'scorm'));
	    	$scormdetails = $DB->get_records_sql("SELECT st.id, st.userid, st.scormid, TIME_TO_SEC(st.value) AS time FROM {scorm_scoes_track} st JOIN {scorm_scoes_track} st1 ON st1.userid = st.userid AND st1.scormid = st.scormid AND st1.scoid AND st.scoid AND st1.attempt = st.attempt WHERE st1.element LIKE 'cmi.core.lesson_status' AND st1.value IN ('passed','completed', 'failed') AND st.element LIKE 'cmi.core.total_time'  AND st1.userid > 2");

	    	if (empty($scormdetails)) {
	    		return true;
	    	}
    		foreach ($scormdetails as $scormdetail) {
    			$coursemoduleid = $DB->get_field('course_modules', 'id', array('module' => $moduleid, 'instance' => $scormdetail->scormid));
    			$courseid = $DB->get_field('scorm', 'course', array('id' => $scormdetail->scormid));
	    		$insertdata = new stdClass();
		        $insertdata->sessionid = rand();
		        $insertdata->userid = $scormdetail->userid;
		        $insertdata->courseid = $courseid;
		        $insertdata->instanceid = $scormdetail->scormid;
		        $insertdata->timespent = $scormdetail->time;
		        $insertdata->activityid = $coursemoduleid;
		        $insertdata->timecreated = time();
		        $insertdata->timemodified = 0;
		        
		        $insertdata1 = new stdClass();
		        $insertdata1->sessionid = rand();
		        $insertdata1->userid = $scormdetail->userid;
		        $insertdata1->courseid = $courseid;
		        $insertdata1->timespent = $scormdetail->time;
		        $insertdata1->timecreated = time();
		        $insertdata1->timemodified = 0;
		        $record1 = $DB->get_record('block_ls_coursetimestats',
		                    array('sessionid' => $insertdata1->sessionid,
		                        'userid' => $insertdata1->userid,
		                    	'courseid' => $insertdata1->courseid,));
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
		    set_config('scormtime', true, 'block_learnerscript');
    	}
    }
    public function userscormtimespent() {
    	global $DB, $PAGE;
    	$scormrecord = get_config('block_learnerscript', 'scormtime');

    	if (!empty($scormrecord)) {
	    	$taskname = '\block_learnerscript\task\userscormtimespent';
	    	$task = \core\task\manager::get_scheduled_task($taskname);
	    	$crontime = $task->get_last_run_time();

	    	if ($crontime > 0) {
		    	$moduleid = $DB->get_field('modules', 'id', array('name' => 'scorm'));
		    	$scormdetails = $DB->get_records_sql("SELECT st.id, st.userid, st.scormid, TIME_TO_SEC(st.value) AS time FROM {scorm_scoes_track} st JOIN {scorm_scoes_track} st1 ON st1.userid = st.userid AND st1.scormid = st.scormid AND st1.scoid AND st.scoid AND st1.attempt = st.attempt WHERE st1.element LIKE 'cmi.core.lesson_status' AND st1.value IN ('passed','completed', 'failed') AND st.element LIKE 'cmi.core.total_time' AND st1.timemodified > $crontime AND st1.userid > 2");

			    if (empty($scormdetails)) {
			    	return true;
			    }
		    	foreach ($scormdetails as $scormdetail) {
		    		$coursemoduleid = $DB->get_field('course_modules', 'id', array('module' => $moduleid, 'instance' => $scormdetail->scormid));
		    		$courseid = $DB->get_field('scorm', 'course', array('id' => $scormdetail->scormid));
		    		$insertdata = new stdClass();
			        $insertdata->sessionid = rand();
			        $insertdata->userid = $scormdetail->userid;
			        $insertdata->courseid = $courseid;
			        $insertdata->instanceid = $scormdetail->scormid;
			        $insertdata->timespent = $scormdetail->time;
			        $insertdata->activityid = $coursemoduleid;
			        $insertdata->timecreated = time();
			        $insertdata->timemodified = 0;
			        $insertdata1 = new stdClass();
			        $insertdata1->sessionid = rand();
			        $insertdata1->userid = $scormdetail->userid;
			        $insertdata1->courseid = $courseid;
			        $insertdata1->timespent = $scormdetail->time;
			        $insertdata1->timecreated = time();
			        $insertdata1->timemodified = 0;
			        $record1 = $DB->get_record('block_ls_coursetimestats',
			                    array('sessionid' => $insertdata1->sessionid,
			                        'userid' => $insertdata1->userid,
			                    	'courseid' => $insertdata1->courseid,));
			        if (!empty($record1)) {
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
				    if ($insertdata->instanceid != 0) {
				        if (!empty($record)) {
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
		    	}			  
	    	}
    	}	
    }
	public function strTime($values) { 
      	$day = intval($values/86400); 
      	$values -= $d*86400; 

      	$hours = intval($values/3600); 
      	$values -= $hours*3600; 

      	$minutes = intval($values/60); 
      	$values -= $minutes*60; 
      	if (!empty($hours)) {
      		$hrs = ($hours == 1) ? $hours. 'hr ' : $hours. 'hrs ';
      	} else {
      		$hrs = '';
      	}

      	if (!empty($minutes)) {
      		$min = $minutes. 'min ';
      	} else {
      		$min = '';
      	}

      	if (!empty($values)) {
      		$sec = $values. 'sec';
      	} else {
      		$sec = '';
      	}

      	if ($day == 1) $days = $day. 'day  ';
      	else if ($day > 1) $days = $day. 'days  ';

      	$result = $days . $hrs . $min . $sec;
	   
      	return $result; 
  	}
}
