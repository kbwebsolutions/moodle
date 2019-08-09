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
require_once $CFG->dirroot . '/lib/gradelib.php';

class plugin_myquizs extends pluginbase{
	public function init(){
		$this->fullname = get_string('myquizs','block_learnerscript');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('myquizs');
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
		global $DB, $OUTPUT;
		switch ($data->column) {
		    case 'finalgrade':
                $row->finalgrade = !is_null($row->finalgrade) ? $row->finalgrade : '--' ;
                break;
            case 'gradepass':
                $row->{$data->column} = isset($row->gradepass) ? $row->gradepass : '--' ;
                break;
            case 'grademax':
                $row->{$data->column} = isset($row->grademax) ? $row->grademax : '--' ;
                break;
			case 'state':
		 		$courserecord = $DB->get_record('course', array('id' => $row->courseid));
		        $completion_info = new completion_info($courserecord);
		        $quizattemptstatus = $DB->get_field_sql("SELECT state FROM {quiz_attempts}
		                                                  WHERE quiz = $row->id AND userid = $user->id
		                                                  ORDER BY id DESC LIMIT 1 ");
		        $quizcomppletion = $DB->get_field_sql("SELECT id FROM {course_modules_completion}
		                                                  WHERE coursemoduleid = $row->activityid AND completionstate > 0
		                                                  AND userid = $user->id
		                                                  ORDER BY id DESC LIMIT 1 ");

		        if (empty($quizattemptstatus) && empty($quizcomppletion)) {
		            $completionstatus = '<span class="notyetstart">Not Yet Started</span>';
		        } else if ($quizattemptstatus == 'inprogress' && empty($quizcomppletion)) {
		            $completionstatus = 'In Progress';
		        } else if ($quizattemptstatus == 'finished' && empty($quizcomppletion)) {
		            $completionstatus = 'Finished';
		        } else if ($quizattemptstatus == 'finished' || !empty($quizcomppletion)) {
		            $cm = new stdClass();
		            $cm->id = $row->activityid;
		            // $completioncretiria = $completion_info->is_enabled();
		            // if (!$completioncretiria) {
		            //     $completionstatus = 'Finished';
		            // } else {
		                $completion = $completion_info->get_data($cm, false, $user->id);
		                switch($completion->completionstate) {
		                    case COMPLETION_INCOMPLETE :
		                        $completionstatus = 'In-Progress';
		                    break;
		                    case COMPLETION_COMPLETE :
		                        $completionstatus = 'Completed';
		                    break;
		                    case COMPLETION_COMPLETE_PASS :
		                        $completionstatus = 'Completed (achieved pass grade)';
		                    break;
		                    case COMPLETION_COMPLETE_FAIL :
		                        $completionstatus = 'Fail';
		                    break;
		                // }
		            }
		        }
		       $row->{$data->column} =  !empty($completionstatus) ? $completionstatus : '--';
		    break;
            case 'status':
            if($row->finalgrade >= $row->gradepass){
                $row->{$data->column} = 'Pass';
            }else if(is_null($row->finalgrade) || $row->finalgrade == '--' || $row->gradepass == $row->grademin || ($row->gradetype == GRADE_TYPE_SCALE && !grade_floats_different($row->gradepass, 0.0))){
                $row->{$data->column} = '--';
            }else{
                $row->{$data->column} = 'Fail';
            }

            break;
            case 'highestgrade':
                $row->{$data->column} = isset($row->highestgrade) ? $row->highestgrade : 0 ;
            break;
            case 'lowestgrade':
                $row->{$data->column} = isset($row->lowestgrade) ? $row->lowestgrade : 0 ;
            break;

        }
		return (isset($row->{$data->column}))? $row->{$data->column} : '';
	}
}
