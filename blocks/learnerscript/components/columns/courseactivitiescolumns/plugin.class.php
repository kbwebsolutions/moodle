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
 * LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\reportbase;
use block_learnerscript\local\ls;

class plugin_courseactivitiescolumns extends pluginbase {

	public function init() {
		$this->fullname = get_string('courseactivitiescolumns', 'block_learnerscript');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('courseactivities');
	}

	public function summary($data) {
		return format_string($data->columname);
	}

	public function colformat($data) {
		$align = (isset($data->align)) ? $data->align : '';
		$size = (isset($data->size)) ? $data->size : '';
		$wrap = (isset($data->wrap)) ? $data->wrap : '';
		return array($align, $size, $wrap);
	}

	// Data -> Plugin configuration data.
	// Row -> Complet course row c->id, c->fullname, etc...
	public function execute($data, $row, $user, $courseid, $starttime = 0, $endtime = 0) {
        global $CFG, $DB, $OUTPUT, $USER;
        $context = context_system::instance();
		switch($data->column){
            case 'activityname':
                $module = $DB->get_field('modules','name',array('id'=>$row->moduleid));
                $activityicon = $OUTPUT->pix_icon('icon', ucfirst($module), $module, array('class' => 'icon'));
                $url = new moodle_url('/mod/'.$module.'/view.php',
                             array('id' => $row->activityid));
                if($module == "workshop"){
                    $row->{$data->column} = $activityicon . html_writer::tag('a', $row->modulename, array('href' => $url));
                }else{
                    $row->{$data->column} = $activityicon . html_writer::tag('a', $row->activityname, array('href' => $url));
                }
            break;
            case 'highestgrade':
            case 'lowestgrade':
            case 'averagegrade':
            case 'grademax':
            case 'gradepass':
                $row->{$data->column} = $row->{$data->column} ? $row->{$data->column} : '0';
            break;
            case 'progress':
				$row->{$data->column} =  "<div class='spark-report' id='spark-report$row->activityid' data-sparkline='$row->progress; progressbar' data-labels = 'progress' >" . $row->progress . "</div>";
            break;
            case 'grades';
    			$gradesReportID = $DB->get_field('block_learnerscript', 'id', array('type' => 'grades'));
                $checkpermissions =  empty($gradesReportID) ? false : (new reportbase($gradesReportID))->check_permissions($USER->id, $context);
                if(empty($gradesReportID) || empty($checkpermissions)){
                    $row->{$data->column} = 'N/A';
                } else{
                    $row->{$data->column} =  html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$gradesReportID&filter_courses=$row->courseid&filter_activity=$row->activityid&filter_coursecategories=$row->categoryid", 'Grades');
                }
            break;
            case 'totaltimespent':
                $row->{$data->column} = $row->{$data->column} ? (new ls)->strTime($row->{$data->column}) : '--';
            break;
            case 'numviews':
                $reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'noofviews'));
                    return html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$reportid&filter_courses=$row->courseid&filter_activity=$row->activityid&filter_coursecategories=$row->categoryid", get_string('numviews', 'report_outline', $row), array("target" => "_blank"));
            break;
            case 'description':
                $row->{$data->column} = $row->description ? $row->description : '--';
                break;
            
        }
		return (isset($row->{$data->column}))? $row->{$data->column} : '';
	}

}
