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

$mypdsettings = new admin_settingpage('thememypdfeaturespots', get_string('featurespots', 'theme_mypd'));

// Feature spots settings.
// Feature spot instructions.
$name = 'theme_mypd/fs_instructions';
$heading = '';
$description = get_string('featurespotshelp', 'theme_mypd');
$setting = new admin_setting_heading($name, $heading, $description);
$mypdsettings->add($setting);

// Feature spots heading.
$name = 'theme_mypd/fs_heading';
$title = new lang_string('featurespotsheading', 'theme_mypd');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 50);
$mypdsettings->add($setting);

// Feature spot images.
$name = 'theme_mypd/fs_one_image';
$title = new lang_string('featureoneimage', 'theme_mypd');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'fs_one_image', 0, $opts);
$mypdsettings->add($setting);

$name = 'theme_mypd/fs_two_image';
$title = new lang_string('featuretwoimage', 'theme_mypd');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'fs_two_image', 0, $opts);
$mypdsettings->add($setting);

$name = 'theme_mypd/fs_three_image';
$title = new lang_string('featurethreeimage', 'theme_mypd');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'fs_three_image', 0, $opts);
$mypdsettings->add($setting);

// Feature spot titles.
$name = 'theme_mypd/fs_one_title';
$title = new lang_string('featureonetitle', 'theme_mypd');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$mypdsettings->add($setting);

$name = 'theme_mypd/fs_two_title';
$title = new lang_string('featuretwotitle', 'theme_mypd');
$setting = new admin_setting_configtext($name, $title, $description, $default);
$mypdsettings->add($setting);

$name = 'theme_mypd/fs_three_title';
$title = new lang_string('featurethreetitle', 'theme_mypd');
$setting = new admin_setting_configtext($name, $title, $description, $default);
$mypdsettings->add($setting);

// Feature spot text.
$name = 'theme_mypd/fs_one_text';
$title = new lang_string('featureonetext', 'theme_mypd');
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$mypdsettings->add($setting);

$name = 'theme_mypd/fs_two_text';
$title = new lang_string('featuretwotext', 'theme_mypd');
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$mypdsettings->add($setting);

$name = 'theme_mypd/fs_three_text';
$title = new lang_string('featurethreetext', 'theme_mypd');
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$mypdsettings->add($setting);

$settings->add($mypdsettings);
