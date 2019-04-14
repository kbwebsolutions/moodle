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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/catalogue/lib.php');

$cats = array();

//get list of categories for Multiselect.
$categories = get_category_list();
foreach ($categories as $cat) {
    $cats[$cat->id] = format_string($cat->name);
}

$settings = new admin_settingpage('Catalogue', new lang_string('pluginname', 'local_catalogue'));

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
        'categoryconfig',
        get_string('setting:categoryconfig', 'local_catalogue'),
        get_string('setting:categoryconfig_desc', 'local_catalogue')
    ));

    $settings->add(new admin_setting_configmultiselect(
        'catalogue_category_list',
        get_string('setting:categorylist_desc', 'local_catalogue'),
        get_string('setting:categorylist', 'local_catalogue'),
        array(),
        $cats
    ));
}

$ADMIN->add('localplugins', $settings);