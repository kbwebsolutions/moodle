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
 * Report Tiles for dashboard block instances.
 * @package  block_reporttiles
 * @author sreekanth <sreekanth@eabyas.in>
 */
use block_learnerscript\local\ls;

defined('MOODLE_INTERNAL') || die();

class block_reporttiles extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_reporttiles');
    }

    public function has_config() {
        return true;
    }
    public function instance_allow_multiple() {
        return true;
    }
    public function get_required_javascript() {
        global $COURSE;
        $this->page->requires->js('/blocks/reporttiles/js/jscolor.min.js');
        $this->page->requires->js(new moodle_url('https://learnerscript.com/wp-content/plugins/learnerscript/js/highcharts.js'));
        $this->page->requires->js('/blocks/learnerscript/js/highcharts/exporting.js');
        $this->page->requires->js('/blocks/learnerscript/js/highcharts/highcharts-more.js');
        $this->page->requires->js('/blocks/learnerscript/js/highcharts/treemap.js');
        $this->page->requires->js('/blocks/learnerscript/js/highmaps/map.js');
        $this->page->requires->js('/blocks/learnerscript/js/highmaps/world.js');
        $this->page->requires->js('/blocks/learnerscript/js/highcharts/solid-gauge.js');
        $this->page->requires->js_call_amd('block_learnerscript/reportwidget', 'CreateDashboardTile',
                                               array(array('reportid' => $this->config->reportlist,
                                                            'reporttype' => $this->config->reporttype,
                                                            'blockinstanceid' => $this->instance->id,
                                                            'courseid' => $COURSE->id )));
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function specialization() {
        $newreportblockstring = get_string('newreporttileblock', 'block_reporttiles');
        $reporttile = get_string('reporttile', 'block_reporttiles');
        $this->title = isset($this->config->title) ? format_string($reporttile) : format_string($newreportblockstring);
    }

    public function hide_header() {
        return true;
    }

    public function instance_config_save($data, $nolongerused = false) {
        global $DB;
        $blockcontext = context_block::instance($this->instance->id);
        file_save_draft_area_files($data->logo, $blockcontext->id, 'block_reporttiles', 'reporttiles',
            $data->logo, array('maxfiles' => 1));
        $DB->set_field('block_instances', 'configdata', base64_encode(serialize($data)),
            array('id' => $this->instance->id));
    }

    public function get_content() {
        global $CFG, $DB, $PAGE, $USER, $OUTPUT;
        require_once($CFG->dirroot . '/blocks/reporttiles/lib.php');
        $courseid = optional_param('courseid', 1, PARAM_INT);
        $status = optional_param('status', '', PARAM_TEXT);
        $cmid = optional_param('cmid', 0, PARAM_INT);
        $userid = optional_param('userid', $USER->id, PARAM_INT);

        $output = $this->page->get_renderer('block_reporttiles');
        $reporttileslib = New block_reporttiles_reporttiles();
        $ls = new \block_learnerscript\local\ls;

        if ($this->content !== null) {
            return $this->content;
        }

        $filteropt = new stdClass();
        $filteropt->overflowdiv = true;
        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->text = "";

        if (isset($this->config->reportlist) &&
            $this->config->reportlist &&
            $DB->record_exists('block_learnerscript', array('id' => $this->config->reportlist, 'visible' => 1))) {

            $blockinstanceid = $this->instance->id;
            $blockinstance = unserialize(base64_decode($this->instance->configdata));
            $styletilescolour = (isset($blockinstance->tilescolour)) ? "style=color:#$blockinstance->tilescolour;" : '';
            $instanceurlcheck = (isset($blockinstance->url)) ? $blockinstance->url : '';

            if ($instanceurlcheck) {
                $tilewithlink = 'reporttile_with_link';
            } else {
                $tilewithlink = '';
            }
            $reportid = $this->config->reportlist;
            $reportclass = $ls->create_reportclass($reportid);
            if (!empty($blockinstance->logo)) {
                $logo = $reporttileslib->reporttiles_icon($blockinstance->logo, $this->instance->id, $reportclass->config->name);
            } else {
                $logo = $OUTPUT->image_url('sample_reporttile', 'block_reporttiles');
            }

            $report = $ls->cr_get_reportinstance($this->config->reportlist);

            if (isset($report) && !$report->global) {
                $this->content->text .= '';
            } else if (isset($this->config->reportlist)) {
                $pickedcolor = isset($blockinstance->tilescolourpicker) ? $blockinstance->tilescolourpicker : '#FFF';

                $reportclass->cmid = $cmid;
                $reportclass->courseid = $courseid;
                $reportclass->userid = $userid;
                $reportclass->ls_startdate = 0;
                $reportclass->ls_enddate = time();

                $reportclass->create_report($blockinstanceid);
                $data = $reportclass->finalreport->table;
                $singlerecord = '';
                if (!empty($data->data[0])) {
                    if (count($data->data[0]) == 1) {
                        $singlerecord = 1;
                    }
                }
                if (count($data->head) > 3 || count($data->data) > 1) {
                    // Report writening more than one record.
                    $this->content->text = get_string('writingmultirecords', 'block_reporttiles');
                } else {
                    if (!empty($data->data[0])) {
                        $dataarray = $data->data[0];
                    }
                    $headarray = $data->head;
                    if (!empty($dataarray)) {
                        $width = 100 / count($dataarray) . '%';
                    } else {
                        $width = 100 . '%';
                    }
                    $configtitle = $DB->get_field('block_learnerscript', 'name', array('id' => $this->config->reportlist));
                    if (strlen($configtitle) > 19) {
                        $configtitlefullname = substr($configtitle, 0, 17) . '...';
                    } else {
                        $configtitlefullname = $configtitle;
                    }
                    $i = 0;
                    if (!empty($dataarray)) {
                        foreach ($dataarray as $key => $value) {
                            $i++;
                            if (count($data->head) == 3) {
                                $width = ($i == 3) ? '40%' : '30%';
                            }
                        }
                    }
                }
            }
            $this->config->reporttype == 'table' ? $tableformat = true : $tableformat = false;
            $reporttileformat = isset($this->config->tileformat) ? $this->config->tileformat : '';
            $reporttileformat == 'fill' ? $colorformat = "style = background:#".$pickedcolor.";opacity:0.8" : $colorformat = "style=border-bottom:7px;border-bottom-style:solid;border-bottom-color:#$pickedcolor";

            $reporttiles = new \block_reporttiles\output\reporttile(
                                                              array('styletilescolour' => $styletilescolour,
                                                                     'tile_with_link' => $tilewithlink,
                                                                     'instanceurlcheck' => $instanceurlcheck,
                                                                     'tilelogo' => $logo,
                                                                     'stylecolorpicker' => $pickedcolor,
                                                                     'configtitle' => $configtitle,
                                                                     'config_title_fullname' => $configtitlefullname,
                                                                     'reportid' => $reportid,
                                                                     'instanceid' => $blockinstanceid,
                                                                     'loading' => $OUTPUT->image_url('loading-small',
                                                                        'block_learnerscript'),
                                                                      'reporttype' => $this->config->reporttype,
                                                                      'tableformat' => $tableformat,
                                                                      'tileformat' => $reporttileformat,
                                                                      'colorformat' => $colorformat,
                                                                      'singlerecord' => $singlerecord));
                $this->content->text = $output->render($reporttiles);
        } else {
            $this->content->text = get_string('configurationmessage', 'block_reporttiles');
        }
        return $this->content;
    }
}
