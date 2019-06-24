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

/**
 * This file replaces the legacy STATEMENTS section in db/install.xml,
 * lib.php/modulename_install() post installation hook and partially defaults.php
 *
 * @package   gradingform_tlrubric
 * @copyright Titus Learning by Marcus Green
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require_once('../../../../../config.php');
/**
 * This is called at the beginning of the uninstallation process to give the module
 * a chance to clean-up its hacks, bits etc. where possible.
 *
 * @return bool true if success
 */
gradingform_tlrubric_uninstall();

function gradingform_tlrubric_uninstall() {
    global $DB;
    
    $DB->delete_record('config_plugins',['plugin'=>'gradingform_tlrubric']);

    $dbman = $DB->get_manager();
    $table = new xmldb_table('tlrubric_levels');
    $dbman->drop_table($table);


    $table = new xmldb_table('tlrubric_criteria');
    $dbman->drop_table($table);

    $table = new xmldb_table('tlrubric_fillings');
    $dbman->drop_table($table);

    $table = new xmldb_table('tlrubric_gradehistory');
    $dbman->drop_table($table);
    return true;
}
