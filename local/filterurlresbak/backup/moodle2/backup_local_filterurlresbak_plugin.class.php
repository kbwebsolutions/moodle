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
 *
 * @package   local_filterurlresbak
 * @copyright 2014 Andreas Wagner, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/** Database:
 *  
 * filter_urlresource(id, courseid, coursemoduleid, url, externalurl, title, imgurl, timecreated)
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup grid course format
 */
class backup_local_filterurlresbak_plugin extends backup_local_plugin {

    /**
     * Returns the format information to attach to module element
     */
    protected function define_module_plugin_structure() {

        $plugin = $this->get_plugin_element();
        $userinfo = $this->get_setting_value('users');

        if ($userinfo) {

            $filterurlresource = new backup_nested_element($this->get_recommended_name());
            $plugin->add_child($filterurlresource);

            // ...backupt urlresource.
            $urlresources = new backup_nested_element('urlresources');
            $filterurlresource->add_child($urlresources);

            $urlresource = new backup_nested_element(
                    'urlresource', array(), array('coursemoduleid', 'url', 'externalurl', 'title', 'imgurl', 'timecreated'));
            $urlresources->add_child($urlresource);
            $urlresource->set_source_table('filter_urlresource', array('coursemoduleid' => backup::VAR_PARENTID));
        }
        return $plugin;
    }

}