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
 * Helper functions for tool_modgroupchanger
 *
 * @package    tool_modgroupchanger
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->libdir . '/formslib.php');

function tool_modgroupchanger_extend_navigation_course($navigation, $course, context $context = null) {
    global $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    if ($usersnode = $navigation->get('users')) {

        $str = get_string('pluginname', 'tool_modgroupchanger');
        $url = new moodle_url('/admin/tool/modgroupchanger/index.php', array('courseid' => $context->instanceid));
        $node = navigation_node::create($str, $url, navigation_node::NODETYPE_LEAF, 'tool_modgroupchanger', 'tool_modgroupchanger');

        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $node->make_active();
        }
        $usersnode->add_node($node, 'override');
    }
}

function get_users_for_moving($context) {
    //global $DB;

    $users = get_enrolled_users($context, $withcapability = '', $groupid = 0, $userfields = 'u.*', $orderby = '', $limitfrom = 0, $limitnum = 0);

    return $users;
}

function get_database_activites($courseid, $moduleid) {
    global $DB;


    $sql = "SELECT cm.id, cm.module, cm.instance, act.name
            FROM {course_modules} AS cm
            JOIN {modules} AS mods ON cm.instance = mods.id
            JOIN {data} AS act on cm.instance = act.id
            WHERE cm.course = :course and module = :module";

    $databases = $DB->get_record_sql($sql, ['course' => $courseid, 'module' => $moduleid]);

    return $databases;
}

function get_groups($courseid, $userid) {

}

function update_activity_group($userid, $groupid, $activityid) {
    global $DB;

    $sql = "UPDATE {datarecords} AS dr
            SET dr.groupid = :group
            WHERE dr.userid = :uid AND dr.dataid = :actid";

    $DB->execute($sql, ["group" => $groupid, "uid" => $userid, "actid" => $activityid]);
}