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
class plugin_userassignments extends pluginbase{
	public function init(){
		$this->fullname = get_string('userassignments','block_learnerscript');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('userassignments');
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
		$myassignmentsreportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'myassignments'));
		switch ($data->column) {
			case 'total':
                $url = new moodle_url('/blocks/learnerscript/viewreport.php',
                				array('id' => $myassignmentsreportid, 'filter_users' => $row->userid, 'filter_courses'=> $courseid , 'filter_coursecategories'=> $row->categoryid ));
                $checkpermissions = empty($myassignmentsreportid) ? false :  (new reportbase($myassignmentsreportid))->check_permissions($USER->id, $context);
                if(empty($myassignmentsreportid) || empty($checkpermissions)){
					$total = 'Total';
				} else{
					$total = html_writer::tag('a', 'Total', array('class'=> 'btn', 'href' => $url));
				}
                $row->{$data->column} = $total;
                break;
            case 'totaltimespent':
                $row->{$data->column} = $row->{$data->column} ? (new ls)->strTime($row->{$data->column}) : '--';
            break;
            case 'highestgrade':
                $row->highestgrade = $row->highestgrade ? $row->highestgrade : '--' ;
                break;
            case 'lowestgrade':
                $row->lowestgrade = $row->lowestgrade ? $row->lowestgrade : '--' ;
                break;
		}
		return (isset($row->{$data->column})) ? $row->{$data->column} : '';
	}
}
