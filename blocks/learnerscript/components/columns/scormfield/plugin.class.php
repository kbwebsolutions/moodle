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
use block_learnerscript\local\reportbase;
class plugin_scormfield extends pluginbase {

    public function init() {
        $this->fullname = get_string('scormfield', 'block_learnerscript');
        $this->type = 'advanced';
        $this->form = true;
        $this->reporttypes = array('courses', 'activitystatus', 'courseaverage',
            'popularresources','scorm');
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

    // Data -> Plugin configuration data.
    // Row -> Complet course row c->id, c->fullname, etc...
    public function execute($data, $row, $user, $courseid, $starttime = 0, $endtime = 0) {
        global $DB, $CFG, $OUTPUT, $USER;
        $context = context_system::instance();
        $scormrecord = $DB->get_record('scorm', array('id' => $row->id));
        if (isset($scormrecord->{$data->column})) {
            switch ($data->column) {
                case 'name':
                    $module = $DB->get_field('modules','name',array('id'=>$row->module));
                    $activityicon = $OUTPUT->pix_icon('icon', ucfirst($module), $module, array('class' => 'icon'));
                    $url = new moodle_url('/mod/'.$module.'/view.php',
                                 array('id' => $row->moduleid));
                    $scormrecord->{$data->column} = $activityicon . html_writer::tag('a', $scormrecord->name, array('href' => $url));
                break;
                case 'course':
                    $reportid = $DB->get_field('block_learnerscript', 'id', array('type'=> 'courseprofile'));
                    $coursename = $DB->get_field('course', 'fullname', array('id'=>$scormrecord->course));
                    $checkpermissions = empty($reportid) ? false :  (new reportbase($reportid))->check_permissions($USER->id, $context);
                    if(empty($reportid) || empty($checkpermissions)){
                        $scormrecord->{$data->column} = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$scormrecord->course.'" target="_blank" class="edit">'.$coursename.'</a>';
                    } else{
                        $scormrecord->{$data->column} = '<a href="'.$CFG->wwwroot.'/blocks/learnerscript/viewreport.php?id='.$reportid.'&filter_courses='.$scormrecord->course.'&filter_coursecategories='.$row->categoryid.'" />'.$coursename.'</a>';
                    }
                break;
                case 'timeopen':
                case 'timeclose':
                case 'timemodified':
                    $scormrecord->{$data->column} = ($scormrecord->{$data->column}) ? userdate($scormrecord->{$data->column}) : '--';
                break;
                case 'options':
                    $scormrecord->{$data->column} = !empty($scormrecord->{$data->column}) ? $scormrecord->{$data->column} : '--';
                break;
            }
        }
       return (isset($scormrecord->{$data->column})) ? $scormrecord->{$data->column} : '';
    }

}
