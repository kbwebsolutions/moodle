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

$mypdsettings = new admin_settingpage('thememypdtopbar', get_string('customtopbar', 'theme_mypd'));

$checked = '1';
$unchecked = '0';

// Use custom colors for navigation bar at top of the screen.
$name = 'theme_mypd/customisenavbar';
$title = new lang_string('customisenavbar', 'theme_mypd');
$description = '';
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$setting->set_updatedcallback('theme_reset_all_caches');
$mypdsettings->add($setting);

// Custom background color of nav bar at top of screen.
$name = 'theme_mypd/navbarbg';
$title = new lang_string('navbarbg', 'theme_mypd');
$description = '';
$default = '#ffffff';
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$mypdsettings->add($setting);

// Color of links in nav bar at top of the screen.
$name = 'theme_mypd/navbarlink';
$title = new lang_string('navbarlink', 'theme_mypd');
$description = '';
$default = '#ff7f41'; // Blackboard Open LMS orange.
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$mypdsettings->add($setting);

// Use custom colors for My Courses button at top of the screen.
$name = 'theme_mypd/customisenavbutton';
$title = new lang_string('customisenavbutton', 'theme_mypd');
$description = '';
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$setting->set_updatedcallback('theme_reset_all_caches');
$mypdsettings->add($setting);

// Color of My Courses link background in nav bar at the top of the screen.
$name = 'theme_mypd/navbarbuttoncolor';
$title = new lang_string('navbarbuttoncolor', 'theme_mypd');
$description = '';
$default = '#ffffff';
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$mypdsettings->add($setting);

// Color of My Courses link text in nav bar at the top of the screen.
$name = 'theme_mypd/navbarbuttonlink';
$title = new lang_string('navbarbuttonlink', 'theme_mypd');
$description = '';
$default = '#ff7f41'; // Blackboard Open LMS orange.
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$mypdsettings->add($setting);

$settings->add($mypdsettings);
