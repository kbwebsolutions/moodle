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
 * myPD settings.
 *
 * @package   theme_mypd
 * @copyright Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$mypdsettings = new admin_settingpage('thememypdpbb', get_string('pbb', 'theme_mypd'));

$name = 'theme_mypd/pbb';
$heading = new lang_string('pbb', 'theme_mypd');
$description = new lang_string('pbb_description', 'theme_mypd');
$setting = new admin_setting_heading($name, $heading, $description);
$mypdsettings->add($setting);

$name = 'theme_mypd/pbb_enable';
$title = get_string('pbb_enable', 'theme_mypd');
$description = get_string('pbb_enable_description', 'theme_mypd');
$setting = new admin_setting_configcheckbox($name, $title, $description, 0);
$mypdsettings->add($setting);

$fields = [
    'user|institution' => get_user_field_name('institution'),
    'user|department' => get_user_field_name('department'),
    'user|address' => get_user_field_name('address'),
    'user|city' => get_user_field_name('city'),
    'user|country' => get_user_field_name('country'),
];

// Get the profile fields which are string type.
$params = [
    'datatype' => 'text'
];
$sql = <<<SQL
        SELECT uf.id, uf.name, cat.name as category
          FROM {user_info_field} uf
          JOIN {user_info_category} cat ON uf.categoryid = cat.id
         WHERE uf.datatype = :datatype
SQL;
$pfields = $DB->get_records_sql($sql, $params);
foreach ($pfields as $pfield) {
    $fields['profile|' . $pfield->id] = '(' . $pfield->category . ') ' . $pfield->name;
}

$name = 'theme_mypd/pbb_field';
$title = get_string('pbb_field', 'theme_mypd');
$description = get_string('pbb_field_description', 'theme_mypd');
$setting = new admin_setting_configselect($name, $title, $description, 'user|department', $fields);
$setting->set_updatedcallback('\\theme_mypd\\local::clean_profile_based_branding_cache');
$mypdsettings->add($setting);

$settings->add($mypdsettings);