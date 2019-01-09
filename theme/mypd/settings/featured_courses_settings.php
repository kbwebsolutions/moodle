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

defined('MOODLE_INTERNAL') || die;// Main settings.

use theme_mypd\admin_setting_configcourseid;
$mypdsettings = new admin_settingpage('thememypdfeaturedcourses', get_string('featuredcourses', 'theme_mypd'));

// Featured courses instructions.
$name = 'theme_mypd/fc_instructions';
$heading = '';
$description = get_string('featuredcourseshelp', 'theme_mypd');
$setting = new admin_setting_heading($name, $heading, $description);
$mypdsettings->add($setting);

// Featured courses heading.
$name = 'theme_mypd/fc_heading';
$title = new lang_string('featuredcoursesheading', 'theme_mypd');
$description = '';
$default = new lang_string('featuredcourses', 'theme_mypd');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW_TRIMMED, 50);
$mypdsettings->add($setting);

// Featured courses.
$name = 'theme_mypd/fc_one';
$title = new lang_string('featuredcourseone', 'theme_mypd');
$description = '';
$default = '0';
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$mypdsettings->add($setting);

$name = 'theme_mypd/fc_two';
$title = new lang_string('featuredcoursetwo', 'theme_mypd');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$mypdsettings->add($setting);

$name = 'theme_mypd/fc_three';
$title = new lang_string('featuredcoursethree', 'theme_mypd');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$mypdsettings->add($setting);

$name = 'theme_mypd/fc_four';
$title = new lang_string('featuredcoursefour', 'theme_mypd');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$mypdsettings->add($setting);

$name = 'theme_mypd/fc_five';
$title = new lang_string('featuredcoursefive', 'theme_mypd');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$mypdsettings->add($setting);

$name = 'theme_mypd/fc_six';
$title = new lang_string('featuredcoursesix', 'theme_mypd');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$mypdsettings->add($setting);

$name = 'theme_mypd/fc_seven';
$title = new lang_string('featuredcourseseven', 'theme_mypd');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$mypdsettings->add($setting);

$name = 'theme_mypd/fc_eight';
$title = new lang_string('featuredcourseeight', 'theme_mypd');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$mypdsettings->add($setting);

// Browse all courses link.
$name = 'theme_mypd/fc_browse_all';
$title = new lang_string('featuredcoursesbrowseall', 'theme_mypd');
$description = new lang_string('featuredcoursesbrowsealldesc', 'theme_mypd');
$checked = '1';
$unchecked = '0';
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$mypdsettings->add($setting);

$settings->add($mypdsettings);
