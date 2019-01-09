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
use theme_mypd\admin_setting_configradiobuttons;

$mypdsettings = new admin_settingpage('thememypdcoursedisplay', get_string('coursedisplay', 'theme_mypd'));

// Course toc display options.
$name = 'theme_mypd/leftnav';
$title = new lang_string('leftnav', 'theme_mypd');
$list = get_string('list', 'theme_mypd');
$top = get_string('top', 'theme_mypd');
$radios = array('list' => $list, 'top' => $top);
$default = 'list';
$description = new lang_string('leftnavdesc', 'theme_mypd');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$mypdsettings->add($setting);

// Resource display options.
$name = 'theme_mypd/resourcedisplay';
$title = new lang_string('resourcedisplay', 'theme_mypd');
$card = new lang_string('card', 'theme_mypd');
$list = new lang_string('list', 'theme_mypd');
$radios = array('list' => $list, 'card' => $card);
$default = 'card';
$description = get_string('resourcedisplayhelp', 'theme_mypd');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$mypdsettings->add($setting);

// Course footer on/off.
$name = 'theme_mypd/coursefootertoggle';
$title = new lang_string('coursefootertoggle', 'theme_mypd');
$description = new lang_string('coursefootertoggledesc', 'theme_mypd');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$mypdsettings->add($setting);

$settings->add($mypdsettings);
