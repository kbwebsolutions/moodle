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

/** LearnerScript
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
defined('MOODLE_INTERNAL') || die();
use block_learnerscript\local\ls;
use block_learnerscript\local\schedule;

class block_learnerscript extends block_list {

    /**
     * Sets the block name and version number
     *
     * @return void
     * */
    public function init() {
        $this->title = get_string('pluginname', 'block_learnerscript');
    }

    public function specialization() {
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_learnerscript');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function instance_allow_config() {
        return true;
    }

    /**
     * Where to add the block
     *
     * @return boolean
     * */
    public function applicable_formats() {
        return array('site' => true, 'course' => true, 'my' => true);
    }

    /**
     * Global Config?
     *
     * @return boolean
     * */
    public function has_config() {
        return true;
    }

    /**
     * More than one instance per page?
     *
     * @return boolean
     * */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with the contents
     * */
    public function get_content() {
        global $DB, $USER, $CFG, $COURSE;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->icons = array();

        if (!isloggedin()) {
            return $this->content;
        }

        $course = $DB->get_record('course', array('id' => $COURSE->id));

        if (!$course) {
            print_error(get_string('nocourseexist', 'block_learnerscript'));
        }

        if ($course->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($course->id);
        }
        $reportdashboardblockexists = $this->page->blocks->is_known_block_type('reportdashboard', false);
        if ($reportdashboardblockexists) {
            $this->content->items[] = '<a class="ls-block_reportdashboard"
            href="' . $CFG->wwwroot . '/blocks/reportdashboard/dashboard.php">' .
            (get_string('pluginname', 'block_learnerscript')) . '</a>';
        }
        // Site (Shared) reports.
        if (!empty($this->config->displayglobalreports)) {
            $reports = $DB->get_records('block_learnerscript', array('global' => 1), 'name ASC');

            if ($reports) {
                foreach ($reports as $report) {
                    if ($report->visible && (new ls)->cr_check_report_permissions($report,
                                                    $USER->id, $context)) {
                        $rname = format_string($report->name);
                        $this->content->items[] = '<a class="ls-block_reportlist_reportname" href= "' .
                        $CFG->wwwroot . '/blocks/learnerscript/viewreport.php?id=' .
                        $report->id . '&courseid=' . $course->id . '" alt="' . $rname . '">' .
                        $rname . '</a>';
                    }
                }
                if (!empty($this->content->items)) {
                    $this->content->items[] = '========';
                }
            }
        }

        $reports = $DB->get_records('block_learnerscript', array('courseid' => $course->id), 'name ASC');

        if ($reports) {
            foreach ($reports as $report) {
                if (!$report->global && $report->visible && (new ls)->cr_check_report_permissions($report, $USER->id, $context)) {
                    $rname = format_string($report->name);
                    $this->content->items[] = '<a class="ls-block_reportlist_reportname"
                    href= "' . $CFG->wwwroot . '/blocks/learnerscript/viewreport.php?id=' .
                    $report->id . '&courseid=' . $course->id . '" alt="' . $rname . '">' .
                    $rname . '</a>';
                }
            }
        }

        if (has_capability('block/learnerscript:managereports', $context) ||
            has_capability('block/learnerscript:manageownreports', $context)) {
            $this->content->items[] = '<a class="ls-block_managereports"
                href="' . $CFG->wwwroot . '/blocks/learnerscript/managereport.php?courseid=' .
                $course->id . '">' . (get_string('managereports', 'block_learnerscript')) .
                '</a>';
        }

        if (is_siteadmin()) {
            $this->content->items[] = '<a class="ls-block_resetconfig"
            href="' . $CFG->wwwroot . '/blocks/learnerscript/lsconfig.php?reset=1">' .
            (get_string('lsresetconfig', 'block_learnerscript')) . '</a>';
        }

        return $this->content;
    }
}