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
 * Global search block.
 *
 * @package    block_edwiser_server_monitor
 * @copyright  Wisdmlabs 2019
 * @author     Yogesh Shirsath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/edwiser_server_monitor/classes/output/renderer.php');

class block_edwiser_server_monitor extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_edwiser_server_monitor');
    }

    /**
     * This function is called on your subclass right after an instance is loaded
     * Use this function to act on instance data just after it's loaded and before anything else is done
     * For instance: if your block will have different title's depending on location (site, course, blog, etc)
     *
     * @return void
     */
    public function specialization() {
        $this->title = !empty($this->config->title) ? $this->config->title : get_string('pluginname', 'block_edwiser_server_monitor');
    }

    /**
     * Gets the block contents.
     *
     * If we can avoid it better not check the server status here as connecting
     * to the server will slow down the whole page load.
     *
     * @return string The block HTML.
     */
    public function get_content() {
        global $PAGE, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        $renderer = $this->page->get_renderer('block_edwiser_server_monitor');

        $refreshrate = isset($this) && isset($this->config) && isset($this->config->refreshrate) ? $this->config->refreshrate : 5;

        $stringmanager = get_string_manager();
        $strings = $stringmanager->load_component_strings('block_edwiser_server_monitor', 'en');
        $PAGE->requires->strings_for_js(array_keys($strings), 'block_edwiser_server_monitor');

        $PAGE->requires->data_for_js('refreshrate', $refreshrate);
        $PAGE->requires->data_for_js('totalmemory', get_total_memory());
        $PAGE->requires->data_for_js('totalstorage', get_total_storage());

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/blocks/edwiser_server_monitor/amd/src/main.js'));

        $this->content = new stdClass();
        $this->content->footer = '';

        $data = new stdClass;

        // Render live status
        $data->live_status = $renderer->render(new \block_edwiser_server_monitor\output\live_status());

        // Render last 24 hours usage
        $data->last_24_hours_usage = $renderer->render(new \block_edwiser_server_monitor\output\last_24_hours_usage($this->instance));

        // Install new plugin page url
        $data->installnewurl = (new moodle_url('/admin/tool/installaddon/index.php'))->__toString();

        // Render recommendation view
        $data->recommendation = $renderer->render(new \block_edwiser_server_monitor\output\recommendation($this->instance));

        // Render contactus view
        $data->contactus = $renderer->render(new \block_edwiser_server_monitor\output\contactus());

        $this->content->text = $renderer->render_from_template('block_edwiser_server_monitor/main', $data);
        return $this->content;
    }

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my' => true);
    }

    /**
     * Are you going to allow multiple instances of each block?
     * If yes, then it is assumed that the block WILL USE per-instance configuration
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        // Are you going to allow multiple instances of each block?
        // If yes, then it is assumed that the block WILL USE per-instance configuration
        return false;
    }
}
