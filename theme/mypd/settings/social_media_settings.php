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

use theme_mypd\admin_setting_configurl;

$mypdsettings = new admin_settingpage('thememypdsocialmedia', get_string('socialmedia', 'theme_mypd'));

    // Social media.
    $name = 'theme_mypd/facebook';
    $title = new lang_string('facebook', 'theme_mypd');
    $description = new lang_string('facebookdesc', 'theme_mypd');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $mypdsettings->add($setting);

    $name = 'theme_mypd/twitter';
    $title = new lang_string('twitter', 'theme_mypd');
    $description = new lang_string('twitterdesc', 'theme_mypd');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $mypdsettings->add($setting);

    $name = 'theme_mypd/linkedin';
    $title = new lang_string('linkedin', 'theme_mypd');
    $description = new lang_string('linkedindesc', 'theme_mypd');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $mypdsettings->add($setting);

    $name = 'theme_mypd/youtube';
    $title = new lang_string('youtube', 'theme_mypd');
    $description = new lang_string('youtubedesc', 'theme_mypd');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $mypdsettings->add($setting);

    $name = 'theme_mypd/instagram';
    $title = new lang_string('instagram', 'theme_mypd');
    $description = new lang_string('instagramdesc', 'theme_mypd');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $mypdsettings->add($setting);

    $settings->add($mypdsettings);
