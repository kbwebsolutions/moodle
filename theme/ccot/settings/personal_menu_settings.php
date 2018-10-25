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

$ccotsettings = new admin_settingpage('themeccotpersonalmenu', get_string('personalmenu', 'theme_ccot'));

// Personal menu show course grade in cards.
$name = 'theme_ccot/showcoursegradepersonalmenu';
$title = new lang_string('showcoursegradepersonalmenu', 'theme_ccot');
$description = new lang_string('showcoursegradepersonalmenudesc', 'theme_ccot');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$ccotsettings->add($setting);

// Personal menu deadlines on/off.
$name = 'theme_ccot/deadlinestoggle';
$title = new lang_string('deadlinestoggle', 'theme_ccot');
$description = new lang_string('deadlinestoggledesc', 'theme_ccot');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$ccotsettings->add($setting);

// Personal menu recent feedback & grading  on/off.
$name = 'theme_ccot/feedbacktoggle';
$title = new lang_string('feedbacktoggle', 'theme_ccot');
$description = new lang_string('feedbacktoggledesc', 'theme_ccot');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$ccotsettings->add($setting);

// Personal menu messages on/off.
$name = 'theme_ccot/messagestoggle';
$title = new lang_string('messagestoggle', 'theme_ccot');
$description = new lang_string('messagestoggledesc', 'theme_ccot');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$ccotsettings->add($setting);

// Personal menu forum posts on/off.
$name = 'theme_ccot/forumpoststoggle';
$title = new lang_string('forumpoststoggle', 'theme_ccot');
$description = new lang_string('forumpoststoggledesc', 'theme_ccot');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$ccotsettings->add($setting);

// Personal menu display on login on/off.
$name = 'theme_ccot/personalmenulogintoggle';
$title = new lang_string('personalmenulogintoggle', 'theme_ccot');
$description = new lang_string('personalmenulogintoggledesc', 'theme_ccot');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$ccotsettings->add($setting);

$settings->add($ccotsettings);
