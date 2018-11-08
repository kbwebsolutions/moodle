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

$ccotsettings = new admin_settingpage('themeccotbranding', get_string('basics', 'theme_ccot'));

if (!during_initial_install() && !empty(get_site()->fullname)) {
    // Site name setting.
    $name = 'fullname';
    $title = new lang_string('fullname', 'theme_ccot');
    $description = new lang_string('fullnamedesc', 'theme_ccot');
    $description = '';
    $setting = new admin_setting_sitesettext($name, $title, $description, null);
    $ccotsettings->add($setting);
}

// Main theme colour setting.
$name = 'theme_ccot/themecolor';
$title = new lang_string('themecolor', 'theme_ccot');
$description = '';
$default = '#ff7f41'; // Blackboard Open LMS orange.
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$ccotsettings->add($setting);

// Site description setting.
$name = 'theme_ccot/subtitle';
$title = new lang_string('sitedescription', 'theme_ccot');
$description = new lang_string('subtitle_desc', 'theme_ccot');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW_TRIMMED, 50);
$ccotsettings->add($setting);

$name = 'theme_ccot/imagesheading';
$title = new lang_string('images', 'theme_ccot');
$description = '';
$setting = new admin_setting_heading($name, $title, $description);
$ccotsettings->add($setting);

 // Logo file setting.
$name = 'theme_ccot/logo';
$title = new lang_string('logo', 'theme_ccot');
$description = new lang_string('logodesc', 'theme_ccot');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$ccotsettings->add($setting);


// Favicon file setting.
$name = 'theme_ccot/favicon';
$title = new lang_string('favicon', 'theme_ccot');
$description = new lang_string('favicondesc', 'theme_ccot');
$opts = array('accepted_types' => array('.ico', '.png', '.gif'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$ccotsettings->add($setting);

$name = 'theme_ccot/footerheading';
$title = new lang_string('footnote', 'theme_ccot');
$description = '';
$setting = new admin_setting_heading($name, $title, $description);
$ccotsettings->add($setting);

// Custom footer setting.
$name = 'theme_ccot/footnote';
$title = new lang_string('footnote', 'theme_ccot');
$description = new lang_string('footnotedesc', 'theme_ccot');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$ccotsettings->add($setting);

// Advanced branding heading.
$name = 'theme_ccot/advancedbrandingheading';
$title = new lang_string('advancedbrandingheading', 'theme_ccot');
$description = new lang_string('advancedbrandingheadingdesc', 'theme_ccot');
$setting = new admin_setting_heading($name, $title, $description);
$ccotsettings->add($setting);

// Heading font setting.
$name = 'theme_ccot/headingfont';
$title = new lang_string('headingfont', 'theme_ccot');
$description = new lang_string('headingfont_desc', 'theme_ccot');
$default = '"Roboto"';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$ccotsettings->add($setting);

// Serif font setting.
$name = 'theme_ccot/seriffont';
$title = new lang_string('seriffont', 'theme_ccot');
$description = new lang_string('seriffont_desc', 'theme_ccot');
$default = '"Georgia"';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$ccotsettings->add($setting);

// Custom CSS file.
$name = 'theme_ccot/customcss';
$title = new lang_string('customcss', 'theme_ccot');
$description = new lang_string('customcssdesc', 'theme_ccot');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$ccotsettings->add($setting);

$settings->add($ccotsettings);
