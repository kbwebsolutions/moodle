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
use block_learnerscript\local\ls;

class plugin_usercoursescolumns extends pluginbase{
	public function init(){
		$this->fullname = get_string('usercoursescolumns','block_learnerscript');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('usercourses');
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
		switch($data->column){
            case 'timeenrolled':
               $row->{$data->column} = $row->timeenrolled ? userdate($row->timeenrolled, '', $row->timezone) : 'N/A';
        	break;
            case 'marks':
             $row->{$data->column} = $row->{$data->column} ? $row->{$data->column} : '--';
            break;
            case 'grade':
                $row->{$data->column} = $row->{$data->column} ? $row->{$data->column} : '--';
            break;
            case 'completedassginments':
            case 'completedquizs':
            case 'completedscorms':
            case 'bagdesissued':
            case 'completedactivities':
                $row->{$data->column} = $row->{$data->column} ? $row->{$data->column} : '0';
            break;
            case 'progressbar':
            return "<div class='spark-report' id='".html_writer::random_id()."' data-sparkline='$row->progressbar; progressbar' data-labels = 'progress' >" . $row->progressbar . "</div>";
            break;
            case 'status':
              require_once("{$CFG->libdir}/completionlib.php");
              $course = $DB->get_record_sql("SELECT * FROM {course} WHERE id = $courseid");
              $info = new completion_info($course);
              $coursecomplete = $info->is_course_complete($row->userid);
              $criteriacomplete = $info->count_course_user_data($row->userid);
              $params = array(
                  'userid' => $row->userid,
                  'course' => $courseid,
              );
              $ccompletion = new completion_completion($params);

              if ($coursecomplete) {
                  $row->{$data->column} = get_string('complete');
              } else if (!$criteriacomplete && !$ccompletion->timestarted) {
                  $row->{$data->column} = get_string('notyetstarted', 'completion');
              } else {
                  $row->{$data->column} = get_string('inprogress', 'completion');
              }
            break;
            case 'totaltimespent':
                $row->{$data->column} = $row->{$data->column} ? (new ls)->strTime($row->{$data->column}) : '--';
            break;
        }
		return (isset($row->{$data->column}))? $row->{$data->column} : '';
	}
}
