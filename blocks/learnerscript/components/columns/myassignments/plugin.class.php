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
class plugin_myassignments extends pluginbase{
	public function init(){
		$this->fullname = get_string('myassignments','block_learnerscript');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('myassignments');
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
		global $DB, $CFG, $OUTPUT;

		$params = array();
		$params['userid'] = $user->id;
		$datesql = '';
		if ($this->ls_startdate >= 0 && $this->ls_enddate) {
			$datesql = " AND timemodified BETWEEN :startdate AND :enddate ";
			$params['startdate'] = $this->ls_startdate;
			$params['enddate'] = $this->ls_enddate;
		}
		switch ($data->column) {
		    case 'finalgrade':
                $row->finalgrade = $row->finalgrade ? $row->finalgrade : '--' ;
            	break;
            case 'lowestgrade':
            case 'highestgrade':
                $row->{$data->column} = $row->{$data->column} ? $row->{$data->column} : '--' ;
            	break;
            case 'gradepass':
                $row->{$data->column} = isset($row->gradepass) ? $row->gradepass : '--' ;
                break;
            case 'grademax':
                $row->{$data->column} = isset($row->grademax) ? $row->grademax : '--' ;
                break;
			case 'status':
				$courserecord = $DB->get_record('course', array('id' => $row->courseid));
				$completion_info = new completion_info($courserecord);
				$coursemodulecompletion = $DB->get_record_sql("SELECT id FROM {course_modules_completion} WHERE userid = $user->id AND coursemoduleid = $row->activityid");
				if(!empty($coursemodulecompletion)){
					try {
						$cm = new stdClass();
						$cm->id = $row->activityid;
						$completion = $completion_info->get_data($cm, false, $user->id);
						switch ($completion->completionstate) {
						case COMPLETION_INCOMPLETE:
							$completionstatus = 'In-Complete';
							break;
						case COMPLETION_COMPLETE:
							$completionstatus = 'Completed';
							break;
						case COMPLETION_COMPLETE_PASS:
							$completionstatus = 'Completed (achieved pass grade)';
							break;
						case COMPLETION_COMPLETE_FAIL:
							$completionstatus = 'Fail';
							break;
						}
					} catch (exception $e) {
						$completionstatus = 'Not Yet Start';
					}
				} else{
				    $submissionsql = "SELECT id FROM {assign_submission}
				                       WHERE assignment = $row->id AND status = 'submitted'
				                        AND userid = :userid $datesql";
				    $assignsubmission = $DB->get_record_sql($submissionsql, $params);
				    if(!empty($assignsubmission)){
				         $completionstatus = '<span class="completed">Submitted</span>';
				     } else{
				         $completionstatus = '<span class="notyetstart">Not Yet Start</span>';
				     }
				}
				$row->{$data->column} = !empty($completionstatus) ? $completionstatus : '--';
			break;
			case 'totaltimespent':
              $row->{$data->column} = $row->{$data->column} ? (new ls)->strTime($row->{$data->column}) : '--';
            break;
		}
		return (isset($row->{$data->column}))? $row->{$data->column} : '';
	}
}
