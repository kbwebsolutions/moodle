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
  * @author: sowmya<sowmya@eabyas.in>
  * @date: 2016
  */
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\reportbase;
use block_learnerscript\local\ls;
class plugin_coursescolumns extends pluginbase{
	public function init(){
		$this->fullname = get_string('coursescolumns', 'block_learnerscript');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('courses');
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
		global $DB, $CFG, $USER;
        $context = context_system::instance();
		$usercoursesReportID = $DB->get_field('block_learnerscript', 'id', array('type' => 'usercourses'));
		switch($data->column){
			case 'progress':
			 	$progresscheckpermissions =  empty($usercoursesReportID) ? false : (new reportbase($usercoursesReportID))->check_permissions($USER->id, $context);
			    if(empty($usercoursesReportID) || empty($progresscheckpermissions)){
					$avgcompletedlink = $row->progress;
				} else{
					$avgcompletedlink = html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$usercoursesReportID&filter_courses=$row->id&filter_status=all&filter_coursecategories=$row->categoryid", $row->progress,
														array("target" => "_blank"));
				}
				return "<div class='spark-report' id='".html_writer::random_id()."' data-sparkline='$row->progress; progressbar'
						data-labels = 'inprogress, completed' data-link='$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$usercoursesReportID&filter_courses=$row->id&filter_status=all&filter_coursecategories=$row->categoryid' >" . $avgcompletedlink . "</div>";
			break;
			case 'activities':
				$listofactivitiesReportID = $DB->get_field('block_learnerscript', 'id', array('type' => 'courseactivities'));
                $checkpermissions = empty($listofactivitiesReportID) ? false : (new reportbase($listofactivitiesReportID))->check_permissions($USER->id, $context);
			    if(empty($listofactivitiesReportID) || empty($checkpermissions)){
                    return $row->{$data->column} ;
                } else{
                    return html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$listofactivitiesReportID&filter_courses=$row->courseid&filter_coursecategories=$row->categoryid",
                    							 $row->{$data->column},array("target" => "_blank"));
                }

			break;
			case 'enrolments':
				$enrolcheckpermissions = empty($usercoursesReportID) ? false : (new reportbase($usercoursesReportID))->check_permissions($USER->id, $context);
			    if(empty($usercoursesReportID) || empty($enrolcheckpermissions)){
                    return $row->{$data->column} ;
                } else{
                    return html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$usercoursesReportID&filter_courses=$row->courseid&filter_status=all&filter_coursecategories=$row->categoryid", $row->{$data->column}, array("target" => "_blank"));
                }

			break;
			case 'completed':
				$comcheckpermissions = empty($usercoursesReportID) ? false : (new reportbase($usercoursesReportID))->check_permissions($USER->id, $context);
			    if(empty($usercoursesReportID) || empty($comcheckpermissions)){
                    return $row->{$data->column};
                } else{
                    return html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$usercoursesReportID&filter_courses=$row->courseid&filter_status=completed&filter_coursecategories=$row->categoryid", $row->{$data->column}, array("target" => "_blank"));
                }
			break;
			case 'highgrade':
				 $row->{$data->column} = isset($row->highgrade) ? $row->highgrade : 0;
			break;
			case 'lowgrade':
				 $row->{$data->column} = isset($row->lowgrade) ? $row->lowgrade : 0;
			break;
			case 'avggrade':
				 $row->{$data->column} = isset($row->avggrade) ? $row->avggrade : 0;
			break;
			case 'totaltimespent':
                $row->totaltimespent = $row->totaltimespent ? (new ls)->strTime($row->totaltimespent) : '--';
            break;
            case 'numviews':
                $reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'courseviews'));
                return html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$reportid&filter_courses=$row->courseid&filter_coursecategories=$row->categoryid", get_string('numviews', 'report_outline', $row), array("target" => "_blank"));
                // $row->numviews= get_string('numviews', 'report_outline', $row);
            break;
            case 'status':
                $coursestatus = $DB->get_field_sql('SELECT visible FROM {course} WHERE id = ' . $row->courseid);
                if($coursestatus == 1){
                    $coursestat = '<span class="label label-success">' . get_string('active') .'</span>';
                } else if($coursestatus == 0){
                    $coursestat = '<span class="label label-warning">' . get_string('inactive') .'</span>';
                }
                $row->{$data->column} = $coursestat;
            break;
			default:
				return (isset($row->{$data->column}))? $row->{$data->column} : '--';
			break;

		}
		return (isset($row->{$data->column}))? $row->{$data->column} : '--';
	}
}
