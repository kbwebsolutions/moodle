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

use theme_ccot\admin_setting_configcourseid;
$ccotsettings = new admin_settingpage('themeccotfeaturedcourses', get_string('featuredcourses', 'theme_ccot'));

// Featured courses instructions.
$name = 'theme_ccot/fc_instructions';
$heading = '';
$description = get_string('featuredcourseshelp', 'theme_ccot');
$setting = new admin_setting_heading($name, $heading, $description);
$ccotsettings->add($setting);

// Featured courses heading.
$name = 'theme_ccot/fc_heading';
$title = new lang_string('featuredcoursesheading', 'theme_ccot');
$description = '';
$default = new lang_string('featuredcourses', 'theme_ccot');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW_TRIMMED, 50);
$ccotsettings->add($setting);

// Featured courses.
$name = 'theme_ccot/fc_one';
$title = new lang_string('featuredcourseone', 'theme_ccot');
$description = '';
$default = '0';
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$ccotsettings->add($setting);

$name = 'theme_ccot/fc_two';
$title = new lang_string('featuredcoursetwo', 'theme_ccot');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$ccotsettings->add($setting);

$name = 'theme_ccot/fc_three';
$title = new lang_string('featuredcoursethree', 'theme_ccot');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$ccotsettings->add($setting);

$name = 'theme_ccot/fc_four';
$title = new lang_string('featuredcoursefour', 'theme_ccot');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$ccotsettings->add($setting);

$name = 'theme_ccot/fc_five';
$title = new lang_string('featuredcoursefive', 'theme_ccot');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$ccotsettings->add($setting);

$name = 'theme_ccot/fc_six';
$title = new lang_string('featuredcoursesix', 'theme_ccot');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$ccotsettings->add($setting);

$name = 'theme_ccot/fc_seven';
$title = new lang_string('featuredcourseseven', 'theme_ccot');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$ccotsettings->add($setting);

$name = 'theme_ccot/fc_eight';
$title = new lang_string('featuredcourseeight', 'theme_ccot');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$ccotsettings->add($setting);

// Browse all courses link.
$name = 'theme_ccot/fc_browse_all';
$title = new lang_string('featuredcoursesbrowseall', 'theme_ccot');
$description = new lang_string('featuredcoursesbrowsealldesc', 'theme_ccot');
$checked = '1';
$unchecked = '0';
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$ccotsettings->add($setting);

$settings->add($ccotsettings);
