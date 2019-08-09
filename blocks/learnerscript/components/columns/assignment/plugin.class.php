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
  * @author eAbyas Info Solutions
  * @date: 2016
  */
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\ls;

class plugin_assignment extends pluginbase{
	public function init(){
		$this->fullname = get_string('assignment','block_learnerscript');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('assignment');
	}
	public function summary($data){
		return format_string($data->columname);
	}
	public function colformat($data){
		$align = (isset($data->align))? $data->align : '';
		$size = (isset($data->size))? $data->size : '';
		$wrap = (isset($data->wrap))? $data->wrap : '';
		return array($align,$size,$wrap);
	}
	public function execute($data,$row,$user,$courseid,$starttime=0,$endtime=0){
		global $DB, $CFG;
		$gradereportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'grades'));
		switch ($data->column) {

            case 'avggrade':
                $row->{$data->column} = isset($row->avggrade) ? $row->avggrade : '0';
                break;
            case 'gradepass':
                $row->{$data->column} = isset($row->gradepass) ? $row->gradepass : '--';
                break;
            case 'totaltimespent':
                $row->{$data->column} = $row->{$data->column} ?  (new ls)->strTime($row->{$data->column}) : '--';
                break;
            case 'numviews':
                $reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'noofviews'));
                    return html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$reportid&filter_courses=$row->courseid&filter_activity=$row->activityid&filter_coursecategories=$row->categoryid", get_string('numviews', 'report_outline', $row), array("target" => "_blank"));
                break;
            case 'completion':
            // $get_courseid = $DB->get_field('course', 'id', array('fullname'=>$row->course));
            $get_status = $DB->get_field('course_modules', 'completion', array('id'=>$row->activityid, 'course' => $row->courseid));
            $row->{$data->column} = $get_status > 0 ?
                                            '<span class="label label-success">' .  get_string('enabled', 'block_learnerscript') . '</span' :
                                            '<span class="label label-warning">' . get_string('disabled', 'block_learnerscript') . '</span';
            break;
		}
		return (isset($row->{$data->column})) ? $row->{$data->column} : ' ';
	}
}
