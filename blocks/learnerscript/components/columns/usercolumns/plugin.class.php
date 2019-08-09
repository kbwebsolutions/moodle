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

class plugin_usercolumns extends pluginbase{
	public function init(){
		$this->fullname = get_string('usercolumns','block_learnerscript');
		$this->type = 'undefined';
		$this->form = false;
		$this->reporttypes = array('users');
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
		global $DB, $USER;
        $context = context_system::instance();
		$reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'coursesoverview'));
		$quizreportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'myquizs'));
		$assignreportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'myassignments'));
		$userbadgeid = $DB->get_field('block_learnerscript', 'id', array('type' => 'userbadges'));
        $courseoverviewpermissions = empty($reportid) ? false : (new reportbase($reportid))->check_permissions($USER->id, $context);
		switch ($data->column) {
			case 'enrolled':
				$allurl = new moodle_url('/blocks/learnerscript/viewreport.php',
					array('id' => $reportid, 'filter_users' => $row->userid, 'filter_coursecategories' => $row->categoryid));
				if(empty($courseoverviewpermissions) || empty($reportid)){
					$row->{$data->column} = $row->enrolled;
				} else{
					$row->{$data->column} = html_writer::tag('a', $row->enrolled,
					array('href' => $allurl));
				}
	            break;
			case 'inprogress':
				$inprogressurl = new moodle_url('/blocks/learnerscript/viewreport.php',
					array('id' => $reportid, 'filter_users' => $row->userid, 'filter_status' => 'inprogress', 'filter_coursecategories' => $row->categoryid));
				if(empty($courseoverviewpermissions) || empty($reportid)){
					$row->{$data->column} = $row->inprogress;
				} else{
					$row->{$data->column} = html_writer::tag('a', $row->inprogress,
					array('href' => $inprogressurl));
				}
	            break;
			case 'completed':
				$completedurl = new moodle_url('/blocks/learnerscript/viewreport.php',
					array('id' => $reportid, 'filter_users' => $row->userid, 'filter_status' => 'completed', 'filter_coursecategories' => $row->categoryid));
				if(empty($courseoverviewpermissions) || empty($reportid)){
					$row->{$data->column} = $row->completed;
				} else{
					$row->{$data->column} = html_writer::tag('a', $row->completed,
					array('href' => $completedurl));
				}
	            break;
			case 'assignments':
        		$assignpermissions = empty($assignreportid) ? false : (new reportbase($assignreportid))->check_permissions($USER->id, $context);
				$assignmenturl = new moodle_url('/blocks/learnerscript/viewreport.php',
					array('id' => $assignreportid, 'filter_users' => $row->userid, 'filter_coursecategories' => $row->categoryid));
				if(empty($assignpermissions) || empty($assignreportid)){
					$row->{$data->column} = $row->assignments;
				} else{
					$row->{$data->column} = html_writer::tag('a', $row->assignments,
					array('href' => $assignmenturl));
				}
	            break;
			case 'quizes':
        		$quizpermissions = empty($quizreportid) ? false : (new reportbase($quizreportid))->check_permissions($USER->id, $context);
				$quizurl = new moodle_url('/blocks/learnerscript/viewreport.php',
					array('id' => $quizreportid, 'filter_users' => $row->userid, 'filter_coursecategories' => $row->categoryid));
				if(empty($quizpermissions) || empty($quizreportid)){
					$row->{$data->column} = $row->quizes;
				} else{
					$row->{$data->column} = html_writer::tag('a', $row->quizes,
					array('href' => $quizurl));
				}

	            break;
			case 'badges':
        		$badgepermissions = empty($userbadgeid) ? false : (new reportbase($userbadgeid))->check_permissions($USER->id, $context);
				$badgeurl = new moodle_url('/blocks/learnerscript/viewreport.php',
					array('id' => $userbadgeid, 'filter_users' => $row->userid, 'filter_coursecategories' => $row->categoryid));
				if(empty($badgepermissions) || empty($userbadgeid)){
					$row->{$data->column} = $row->badges;
				} else{
						$row->{$data->column} = html_writer::tag('a', $row->badges,
					array('href' => $badgeurl));
				}
	            break;
	        case 'grade':
				$row->{$data->column} = (isset($row->grade))? $row->grade : '--';
	            break;
	        case 'progress':
				return "<div class='spark-report' id='".html_writer::random_id()."' style='width:100px;' data-sparkline='$row->progress; progressbar' 
						data-labels = 'progress' >" . $row->progress . "</div>";
			break;
			 case 'status':
			 	$userstatus = $DB->get_record_sql('SELECT suspended, deleted FROM {user} WHERE id = ' . $row->userid);

			    if($userstatus->suspended){
			    	$userstaus = '<span class="label label-warning">' . get_string('suspended') .'</span>';
			    } else if($userstatus->deleted){
			    	$userstaus = '<span class="label label-warning">' . get_string('deleted') .'</span>';
			    } else{
			    	$userstaus =  '<span class="label label-success">' . get_string('active') .'</span>';
			    }
                $row->{$data->column} = $userstaus;

            break;
		}
		return (isset($row->{$data->column}))? $row->{$data->column} : '';
	}
}
