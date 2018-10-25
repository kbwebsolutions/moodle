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
use theme_ccot\admin_setting_configradiobuttons;

$ccotsettings = new admin_settingpage('themeccotcoursedisplay', get_string('coursedisplay', 'theme_ccot'));

// Course toc display options.
$name = 'theme_ccot/leftnav';
$title = new lang_string('leftnav', 'theme_ccot');
$list = get_string('list', 'theme_ccot');
$top = get_string('top', 'theme_ccot');
$radios = array('list' => $list, 'top' => $top);
$default = 'list';
$description = new lang_string('leftnavdesc', 'theme_ccot');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$ccotsettings->add($setting);

// Resource display options.
$name = 'theme_ccot/resourcedisplay';
$title = new lang_string('resourcedisplay', 'theme_ccot');
$card = new lang_string('card', 'theme_ccot');
$list = new lang_string('list', 'theme_ccot');
$radios = array('list' => $list, 'card' => $card);
$default = 'card';
$description = get_string('resourcedisplayhelp', 'theme_ccot');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$ccotsettings->add($setting);

// Course footer on/off.
$name = 'theme_ccot/coursefootertoggle';
$title = new lang_string('coursefootertoggle', 'theme_ccot');
$description = new lang_string('coursefootertoggledesc', 'theme_ccot');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$ccotsettings->add($setting);

$settings->add($ccotsettings);
