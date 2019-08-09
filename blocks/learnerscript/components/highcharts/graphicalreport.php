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
 * @author: Naveen Kumar <naveen@eabyas.in>
 * @date: 2014
 */
global $CFG, $DB;
require_once $CFG->dirroot . '/config.php';

class highreports {
	/*
		     * Loads the required high chart JS libraries
		     *
	*/
	public function __construct() {}

	/*
		     * @method piechart Generated piechart with given data
		     * @param object $data graph data
		     * @param object $series series values(X axis and Y axis etc...)
		     * @param object $name
		     * @param string $containerid div placeholder ID
		     * @return string pie chart markup with JS code
	*/

	public function piechart($data, $series, $name, $containerid = null, $head) {
		$containerid == null ? $containerid = $series['id'] : null;
		$piedata = $this->get_piedata($data, $series, $head);
		if ($piedata['error']) {
			return $piedata;
		} else {
			empty($series['formdata']->serieslabel) ? $series['formdata']->serieslabel = $name->name : null;
			if (isset($series['formdata']->percentage)) {
				$tooltipvalue = '{point.percentage:.1f}%';
				$legendvalue = '{percentage:.1f} %';
			} else {
				$tooltipvalue = '{point.y}';
				$legendvalue = '{y}';
			}
			$options = [type => 'pie',
						containerid => 'piecontainer' . $containerid . '',
						title => '' . $series['formdata']->chartname . '',
						tooltip => '' . $tooltipvalue . '',
						datalabels => '' . $series['formdata']->datalabels . '',
						showlegend => '' . $series['formdata']->showlegend . '',
						serieslabel => '' . $series['formdata']->serieslabel . '',
						id => $series['id'],
						data => $piedata,
					];
			return $options;
		}
	}

	public function worldmap($data, $series, $name, $containerid = null, $head) {
		$containerid == null ? $containerid = $series['id'] : null;
		$piedata = $this->get_worldmapdata($data, $series, $head);
		if ($piedata['error']) {
			return $piedata;
		} else {
			empty($series['formdata']->serieslabel) ? $series['formdata']->serieslabel = $name->name : null;
			if (isset($series['formdata']->percentage)) {
				$tooltipvalue = '{point.percentage:.1f}%';
				$legendvalue = '{percentage:.1f} %';
			} else {
				$tooltipvalue = '{point.y}';
				$legendvalue = '{y}';
			}

			$options = [type => 'map',
						containerid => 'worldmapcontainer' . $containerid . '',
						title => '' . $series['formdata']->chartname . '',
						tooltip => '' . $tooltipvalue . '',
						datalabels => '' . $series['formdata']->datalabels . '',
						showlegend => '' . $series['formdata']->showlegend . '',
						serieslabel => '' . $series['formdata']->serieslabel . '',
						id => $series['id'],
						data => $piedata,
					];
			return $options;
		}
	}

	public function treemap($data, $series, $name, $containerid = null, $head) {
			$containerid == null ? $containerid = $series['id'] : null;
			$piedata = $this->get_treemapdata($data, $series, $head);
			if ($piedata['error']) {
				return $piedata;
			} else {
				empty($series['formdata']->serieslabel) ? $series['formdata']->serieslabel = $name->name : null;
				if (isset($series['formdata']->percentage)) {
					$tooltipvalue = '{point.percentage:.1f}%';
					$legendvalue = '{percentage:.1f} %';
				} else {
					$tooltipvalue = '{point.y}';
					$legendvalue = '{y}';
				}

				$options = [type => 'treemap',
							containerid => 'treemapcontainer' . $containerid . '',
							title => '' . $series['formdata']->chartname . '',
							tooltip => '' . $tooltipvalue . '',
							datalabels => '' . $series['formdata']->datalabels . '',
							showlegend => '' . $series['formdata']->showlegend . '',
							serieslabel => '' . $series['formdata']->serieslabel . '',
							id => $series['id'],
							data => $piedata,
						];
				return $options;
			}
		}

	/*
		     * Generates linechart/barchart with given data
		     * @param object $data graph data
		     * @param object $series series of values(X axis and Y axis etc...)
		     * @param object $name
		     * @param string $type line or bar
		     * @param string $containerid div container ID of chart
		     * @param array $head
		     * @return string  line/bar chart markup with JS code
	*/

	public function lbchart($data, $series, $name, $type, $containerid = null, $head) {
		$i = 0;
		$containerid == null ? $containerid = $series['id'] : null;
		empty($series['formdata']->serieslabel) ? $series['formdata']->serieslabel = $name->name : null;
		$lbchartdata = $this->get_lbchartdata($data, $series, $type, $head, $name);
		$lbchartdata['dataLabels'] = ['enabled' => true];
		$lbchartdata['borderRadius'] = 5;
		if ($lbchartdata['error']) {
			return $lbchartdata;
		} else {
			$yaxistext = null;
			if ($series['formdata']->calcs) {
				$yaxistext = get_string($series['formdata']->calcs, 'block_learnerscript');
			}

			$container = $type . 'container' . $containerid;
			$options = [type => '' . $type . '',
						containerid => '' . $container . '',
						title => '' . $series['formdata']->chartname . '',
						showlegend => '' . $series['formdata']->showlegend . '',
						serieslabel => '' . $series['formdata']->serieslabel . '',
						categorydata => $lbchartdata['categorylist'],
						id => $series['id'],
						data => $lbchartdata['comdata'],
						datalabels => '' . $series['formdata']->datalabels . '',
						yaxistext => $yaxistext,
						ylabel => $head[$series['formdata']->serieid]
					];
			return $options;
		}
	}
	public function combination_chart($data, $series, $name, $type, $containerid = null, $head, $seriesvalues) {
		$containerid == null ? $containerid = $series['id'] : null;

		foreach ($seriesvalues as $key => $value) {
			if (!in_array($value['id'], $series['formdata']->lsitofcharts)) {
				unset($seriesvalues[$key]);
				continue;
			}
			if (in_array($value['pluginname'], array('line', 'column'))) {
				$reportdata = $this->get_lbchartdata($data, $value, $value['pluginname'], $head, $name);
				if ($reportdata['error']) {
					return $reportdata;
					exit;
				}
				$lbdata[] = $reportdata;
			} else {
				$piedata = $this->get_piedata($data, $value, $head);
			}
		}
		if ($piedata['error']) {
			return $piedata;
			exit;
		}
		foreach ($lbdata as $k => $lb) {
			foreach ($lb['comdata'] as $lbs) {
				$completedata[] = $lbs;
			}
			$categorylist = $lb['categorylist'];
		}

		$completedata[] = [type => "pie",
			data => $piedata,
			center => [50, 50],
			size => 100,
			showInLegend => false,
			dataLabels => [
				enabled => false,
			]];
		$options = [type => 'combination',
			containerid => $containerid,
			title => $series[formdata]->chartname,
			categorydata => $categorylist,
			id => $series['id'],
			data => $completedata];
		return $options;
	}
	public function get_piedata($data, $series, $head) {
		$error = array();
		if (empty($head)) {
			//$error[] = get_string('nodataavailable', 'block_learnerscript');
		} else {
			if (!array_key_exists($series['formdata']->areaname, $head)) {
			//	$error[] = get_string('areaname', 'block_learnerscript', $series['formdata']->areaname);
			} elseif (!array_key_exists($series['formdata']->areavalue, $head)) {
				//$error[] = get_string('areavalue', 'block_learnerscript', $series['formdata']->areavalue);
			}
			$graphdata = array();
			if ($data) {
				foreach ($data as $r) {
					$r[$series['formdata']->areavalue] = strip_tags($r[$series['formdata']->areavalue]);
					if (is_numeric($r[$series['formdata']->areavalue])) {
						$graphdata[] = ['name' => strip_tags($r[$series['formdata']->areaname]), 'y' => $r[$series['formdata']->areavalue]];
					}

				}
			}
		}
		if (empty($error)) {
			return $graphdata;
		} else {
			return array('error' => true, 'messages' => $error);
		}

	}
	public function get_worldmapdata($data, $series, $head) {
			$graphdata = array();
			if ($data) {
				foreach ($data as $r) {
					if($r[$series['formdata']->areaname] == '')
						continue;
					$graphdata[] = ['code'=>strtoupper($r[$series['formdata']->areaname]),
									'name'=>strtoupper($r[$series['formdata']->areaname]),
									'value'=> $r[$series['formdata']->areavalue]];
				}
			}

			return $graphdata;


	}
		public function get_treemapdata($data, $series, $head) {
			$graphdata = array();
			$graphdata[] = ['name'=>"yes"];
			if ($data) {
				foreach ($data as $r) {

					if($r[$series['formdata']->areaname] == '')
						continue;
					$graphdata[] = ['name'=>strtoupper($r[$series['formdata']->areaname]),
									'value'=> $r[$series['formdata']->areavalue],
									'id'=>$r[$series['formdata']->id],
									'parent'=>"yes"];
				}
			}
			return $graphdata;
	}
	public function get_lbchartdata($data, $series, $type, $head, $report) {
		global $CFG;
		$i = 0;
		$error = array();
		if (empty($head)) {
			$error[] = get_string('nodataavailable', 'block_learnerscript');
		} else {
			foreach ($series['formdata']->yaxis as $yaxis) {
				if (array_key_exists($yaxis, $head)) {
					if ($data) {
						$categorylist = array();
						foreach ($data as $r) {
							if($r[$series['formdata']->serieid] =='')
								continue;
							$r[$yaxis] = strip_tags($r[$yaxis]);
							$r[$yaxis] = is_numeric($r[$yaxis]) ? $r[$yaxis] : floatval($r[$yaxis]);
							$seriesdata[] = $r[$series['formdata']->serieid];
							$graphdata[$i][] = $r[$yaxis];
							if (empty($series['formdata']->calcs)) {
								$categorylist[] = $r[$series['formdata']->serieid];
							} else {
								$categorylist = array();
							}
						}
						$i++;
					}
					$heading[] = $yaxis;
				}
			}
			$j = 0;
			$comdata = array();
			if ($series['formdata']->calcs) {
				require_once $CFG->dirroot . '/blocks/learnerscript/components/calcs/' . $series['formdata']->calcs . '/plugin.class.php';

				$classname = 'plugin_' . $series['formdata']->calcs;
				$class = new $classname($report);
				foreach ($graphdata as $k => $gdata) {
					$result[] = $class->execute($gdata);
					$categorylist[] = ucfirst($series['formdata']->calcs) . ' of ' . $head[$series['formdata']->yaxis[$k]];
				}
				$comdata[] = ['data' => $result, 'name' => ucfirst($series['formdata']->calcs), 'type' => $type];
			} else {
				foreach ($graphdata as $k => $gdata) {
					$comdata[] = ['data' => $gdata, 'name' => $head[$heading[$j]], 'type' => $type];
					$j++;
				}
			}
		}
		if (empty($error)) {
			return compact('comdata', 'seriesdata', 'categorylist');
		} else {
			return array('error' => true, 'messages' => $error);
		}
	}
}