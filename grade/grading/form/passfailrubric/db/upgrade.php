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
 * This file keeps track of upgrades to plugin gradingform_passfailrubric
 *
 * @package    gradingform_passfailrubric
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Keeps track or passfailrubric plugin upgrade path
 *
 * @param int $oldversion the DB version of currently installed plugin
 * @return bool true
 */
function xmldb_gradingform_passfailrubric_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2019071000) {
        $table = new xmldb_table('gradingform_pfrbric_grades');

        $field = new xmldb_field('itemid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'instanceid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('explanation', XMLDB_TYPE_CHAR, '512', null, null, null, null, 'grade');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('authoredby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'explanation');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'authoredby');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

            // Passfailrubric savepoint reached.
            upgrade_plugin_savepoint(true, 2019071000, 'gradingform', 'passfailrubric');

      }

      if ($oldversion < 2020022500) {

        // Define table gradingform_pfrbric_moderate to be created.
        $table = new xmldb_table('gradingform_pfrbric_moderate');

        // Adding fields to table gradingform_pfrbric_moderate.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('item', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table gradingform_pfrbric_moderate.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for gradingform_pfrbric_moderate.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

          // Passfailrubric savepoint reached.
          upgrade_plugin_savepoint(true, 2020022500, 'gradingform', 'passfailrubric');
    }
    // Update the csv grade names in the scale table.
    if ($oldversion < 2020042200) {
        $id = $DB->get_field('scale', 'id', ['name' => 'refer_fail_pass']);
        $DB->update_record('scale', ['id' => $id, 'scale'=>'Fail,Refer,Pass']);
        upgrade_plugin_savepoint(true, 2020042200, 'gradingform', 'passfailrubric');
    }

    // Change field type for explination to allow for longer text
    if ($oldversion < 2020042300) {

        // Changing type of field explanation on table gradingform_pfrbric_grades to text.
        $table = new xmldb_table('gradingform_pfrbric_grades');
        $field = new xmldb_field('explanation', XMLDB_TYPE_TEXT, null, null, null, null, null, 'grade');

        // Launch change of type for field explanation.
        $dbman->change_field_type($table, $field);

        // Passfailrubric savepoint reached.
        upgrade_plugin_savepoint(true, 2020042300, 'gradingform', 'passfailrubric');
    }

    return true;
}
