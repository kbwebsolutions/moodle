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

/** to restore data used by the urlresource filter
 *
 * @package   local_filterurlresbak
 * @copyright 2014 Andreas Wagner, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_local_filterurlresbak_plugin extends restore_local_plugin {

    protected function define_module_plugin_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('users');

        if ($userinfo) {

            $elename = 'urlresource';
            $elepath = $this->get_pathfor('/urlresources/urlresource');
            $paths[] = new restore_path_element($elename, $elepath);
        }

        return $paths; // And we return the interesting paths.
    }

    public function process_urlresource($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->coursemoduleid = $this->task->get_moduleid();

        if (preg_match('/\$@URLVIEWBYID*([^@]*)@\$/i', $data->url)) {
            $url = new moodle_url('/mod/url/view.php', array('id' => $data->coursemoduleid));
            $data->url = $url->out();
        }

        $DB->insert_record('filter_urlresource', $data);
    }

}