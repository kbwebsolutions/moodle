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
  * @author: arun<arun@eabyas.in>
  * @date: 2016
  */
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\reportbase;
use block_learnerscript\local\ls;

class plugin_usersscormcolumns extends pluginbase{
    public function init(){
        $this->fullname = get_string('usersscorm','block_learnerscript');
        $this->type = 'undefined';
        $this->form = true;
        $this->reporttypes = array('usersscorm');
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
        global $CFG, $DB, $OUTPUT;
        $context = context_system::instance();
        $myscormreportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'myscorm'));
        switch($data->column){
            case 'firstaccess':
                return (isset($row->{$data->column}))? userdate($row->{$data->column}) : '--';
            break;
            case 'lastaccess':
                $attempt = $DB->get_field_sql("SELECT MAX(sst.attempt) FROM {scorm_scoes_track} sst 
                    JOIN {scorm} s ON s.id = sst.scormid WHERE sst.userid = $row->userid AND s.course = $courseid ");
                if (!empty($attempt)) {
                    $value = $DB->get_field_sql("SELECT sst.timemodified FROM {scorm_scoes_track} sst 
                                            JOIN {scorm} s ON s.id = sst.scormid 
                                            WHERE sst.element = 'cmi.core.total_time' 
                                            AND sst.userid = $row->userid AND s.course = $courseid 
                                            AND sst.attempt = $attempt");
                    if (empty($value)) {
                        $lastaccess = $DB->get_field_sql("SELECT sst.timemodified FROM {scorm_scoes_track} sst 
                                                JOIN {scorm} s ON s.id = sst.scormid 
                                                WHERE sst.userid = $row->userid AND s.course = $courseid 
                                                AND sst.element = 'x.start.time' AND sst.attempt = $attempt
                                                ");
                        $row->{$data->column} = $lastaccess ? userdate($lastaccess) : '--';
                    } else {
                        $row->{$data->column} = $value ? userdate($value) : '--';
                    }
                }
            break;
            case 'total':
                $myscormpermissions = empty($myscormreportid) ? false : (new reportbase($myscormreportid))->check_permissions($USER->id, $context);
                $url = new moodle_url('/blocks/learnerscript/viewreport.php',
                                array('id' => $myscormreportid, 'filter_users' => $row->userid ,'filter_courses' => $courseid, 'filter_coursecategories' => $row->categoryid));
                $total = html_writer::tag('a', 'Total', array('class'=> 'btn', 'href' => $url));

                if(empty($myscormpermissions) || empty($myscormreportid)){
                    $row->{$data->column} = 'Total';
                } else{
                    $row->{$data->column} = $total;
                }
            break;
            case 'totaltimespent':
                $row->{$data->column} = $row->{$data->column} ? (new ls)->strTime($row->{$data->column}) : '--';
            break;
        }
        return (isset($row->{$data->column}))? $row->{$data->column} : '--';
    }
}
