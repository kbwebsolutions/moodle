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

class plugin_useractivitiescolumns extends pluginbase{
    public function init(){
        $this->fullname = get_string('useractivities','block_learnerscript');
        $this->type = 'undefined';
        $this->form = true;
        $this->reporttypes = array('useractivities', 'popularresources');
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
        global $CFG,$DB,$OUTPUT;
        switch($data->column){
            case 'highestgrade':
            case 'lowestgrade':
            case 'finalgrade':
                return (isset($row->{$data->column}))? $row->{$data->column} : '--';
            break;
            case 'firstaccess':
            case 'lastaccess':
            case 'completedon':
                $row->{$data->column} = (isset($row->{$data->column})) ? userdate($row->{$data->column}) : '--';
            break;
            case 'modulename':
                $module = $DB->get_field('modules','name',array('id' => $row->module));
                $activityicon = $OUTPUT->pix_icon('icon', ucfirst($module), $module, array('class' => 'icon'));
                $url = new moodle_url('/mod/'.$module.'/view.php',
                             array('id' => $row->activityid));
                $row->{$data->column} = $activityicon . html_writer::tag('a', $row->modulename, array('href' => $url));
            break;
            case 'moduletype':
                $activityicon1 = $OUTPUT->pix_icon('icon', ucfirst($row->moduletype), $row->moduletype, array('class' => 'icon'));
                $row->{$data->column} = $activityicon1 . ucfirst($row->moduletype);
            break;
            case 'completionstatus':
                switch($row->completionstatus) {
                    case 0 : $completiontype='n'; break;
                    case 1 : $completiontype='y'; break;
                    case 2 : $completiontype='pass'; break;
                    case 3 : $completiontype='fail'; break;
                }

                $row->completionstatus = $completiontype ? get_string('completion-' . $completiontype, 'completion') : 'N/A';
            break;
            case 'totaltimespent':
                $row->{$data->column} = $row->{$data->column} ? (new ls)->strTime($row->{$data->column}) : '--';
            break;
            case 'numviews':
                $row->{$data->column} =  $row->{$data->column};
            break;
        }
        return (isset($row->{$data->column}))? $row->{$data->column} : '--';
    }
}
