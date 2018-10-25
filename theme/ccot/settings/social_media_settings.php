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

use theme_ccot\admin_setting_configurl;

$ccotsettings = new admin_settingpage('themeccotsocialmedia', get_string('socialmedia', 'theme_ccot'));

    // Social media.
    $name = 'theme_ccot/facebook';
    $title = new lang_string('facebook', 'theme_ccot');
    $description = new lang_string('facebookdesc', 'theme_ccot');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $ccotsettings->add($setting);

    $name = 'theme_ccot/twitter';
    $title = new lang_string('twitter', 'theme_ccot');
    $description = new lang_string('twitterdesc', 'theme_ccot');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $ccotsettings->add($setting);

    $name = 'theme_ccot/linkedin';
    $title = new lang_string('linkedin', 'theme_ccot');
    $description = new lang_string('linkedindesc', 'theme_ccot');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $ccotsettings->add($setting);

    $name = 'theme_ccot/youtube';
    $title = new lang_string('youtube', 'theme_ccot');
    $description = new lang_string('youtubedesc', 'theme_ccot');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $ccotsettings->add($setting);

    $name = 'theme_ccot/instagram';
    $title = new lang_string('instagram', 'theme_ccot');
    $description = new lang_string('instagramdesc', 'theme_ccot');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $ccotsettings->add($setting);

    $settings->add($ccotsettings);
