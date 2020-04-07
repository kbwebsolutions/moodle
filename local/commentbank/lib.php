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
 * Spreadsheet export report for assignments marked with advanced grading methods
 *
 * @package    local_commentbank
 * @copyright  2019 Titus Learning by Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the module navigation
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param cm_info $cm
 */
function local_commentbank_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    if (has_capability('local/commentnbank:create_site_comments', $context)) {
        $url = new moodle_url('/local/commentbank/index.php', array('id' => $course->id));
        $parentnode->add(get_string('pluginname', 'local_commentbank'), $url, navigation_node::TYPE_SETTING, null,
        'commentbank', new pix_icon('t/viewdetails', 'comment bank'));
    }
}

/**
 * Serves 3rd party js files.
 * (c) Guy Thomas 2018
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_commentbank_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG;

    $pluginpath = __DIR__.'/';

    if ($filearea === 'vendorjs') {
        // Typically CDN fall backs would go in vendorjs.
        $path = $pluginpath.'vendorjs/'.implode('/', $args);
        send_file($path, basename($path));
        return true;
    } else if ($filearea === 'vue') {
        // Vue components.
        $jsfile = array_pop ($args);
        $compdir = basename($jsfile, '.js');
        $umdfile = $compdir.'.umd.js';
        $args[] = $compdir;
        $args[] = 'dist';

        if ($CFG->cachejs) {
            $pathinfo = (object) pathinfo($umdfile);
            $args[] = $pathinfo->filename.'.min.js';
        } else {
            $args[] = $umdfile;
        }

        $path = $pluginpath.'vue/'.implode('/', $args);
        send_file($path, basename($path));
        return true;
    } else {
        die('unsupported file area');
    }
    die;
}

