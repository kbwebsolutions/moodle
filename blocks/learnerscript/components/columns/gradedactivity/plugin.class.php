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
use block_learnerscript\local\ls;

class plugin_gradedactivity extends pluginbase {

    public function init() {
        $this->fullname = get_string('listofactivities', 'block_learnerscript');
        $this->type = 'undefined';
        $this->form = true;
        $this->reporttypes = array('courses', 'grades','activities');
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

    public function execute($data, $row, $user, $courseid, $starttime = 0, $endtime = 0) {
        global $CFG,$DB,$OUTPUT;
        switch($data->column){
                case 'modulename':
                    $module = $DB->get_field_sql('select name from {modules} as m JOIN {course_modules} as cm on m.id=cm.module
                                                   where cm.id='.$row->activityid.'');
                    $activityicon = $OUTPUT->pix_icon('icon', ucfirst($module), $module, array('class' => 'icon'));
                    $module = $DB->get_field_sql('select name from {modules} where id=(select module from {course_modules} where id='.$row->activityid.')');
                    $row->{$data->column} = $activityicon . html_writer::link("$CFG->wwwroot/mod/$module/view.php?id=$row->activityid",
                                                                               $row->{$data->column},array("target" => "_blank"));
                break;
                case 'highestgrade':
                case 'lowestgrade':
                case 'averagegrade':
                    $row->{$data->column} = $row->{$data->column} == null ? '0' : $row->{$data->column};
                break;
                case 'totaltimespent':
                    $row->{$data->column} = $row->{$data->column} ?  (new ls)->strTime($row->{$data->column}) : '--';
                break;
                case 'numviews':
                    if ($this->colformat) {
                        return $row->numviews;
                    } else {
                        $reportid = $DB->get_field('block_learnerscript', 'id', array('type' => 'noofviews'));
                        return html_writer::link("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=$reportid&filter_courses=$row->courseid&filter_activity=$row->activityid", get_string('numviews', 'report_outline', $row), array("target" => "_blank"));
                    }
                    // $row->{$data->column} = get_string('numviews', 'report_outline', $row);
                break;
                case 'description':
                    $row->{$data->column} = $row->description ? $row->description : '--';
                break;
        }
        return (isset($row->{$data->column}))? $row->{$data->column} : ' -- ';
    }
}