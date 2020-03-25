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
 * @package    filter
 * @subpackage urlresource
 * @copyright  2014 Andreas Wagner, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_filter_urlresource_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014051203) {

        // Define table filter_urlresource to be created.
        $table = new xmldb_table('filter_urlresource');

        // Adding fields to table filter_urlresource.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '200', null, null, null, null);
        $table->add_field('imgurl', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table filter_urlresource.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for filter_urlresource.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Urlresource savepoint reached.
        upgrade_plugin_savepoint(true, 2014051203, 'filter', 'urlresource');
    }

    if ($oldversion < 2014051204) {

        // Define field coursemoduleid to be added to filter_urlresource.
        $table = new xmldb_table('filter_urlresource');
        $field = new xmldb_field('coursemoduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'courseid');

        // Conditionally launch add field coursemoduleid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('graburl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'url');

        // Conditionally launch add field graburl.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Urlresource savepoint reached.
        upgrade_plugin_savepoint(true, 2014051204, 'filter', 'urlresource');
    }

    if ($oldversion < 2014051206) {
        // Urlresource savepoint reached.
        upgrade_plugin_savepoint(true, 2014051206, 'filter', 'urlresource');
    }

    if ($oldversion < 2014051207) {
        // Urlresource savepoint reached.
        upgrade_plugin_savepoint(true, 2014051207, 'filter', 'urlresource');
    }

    if ($oldversion < 2014051208) {

        // Rename field externalurl on table filter_urlresource to NEWNAMEGOESHERE.
        $table = new xmldb_table('filter_urlresource');
        $field = new xmldb_field('graburl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'url');

        // Launch rename field externalurl.
        $dbman->rename_field($table, $field, 'externalurl');

        // Urlresource savepoint reached.
        upgrade_plugin_savepoint(true, 2014051208, 'filter', 'urlresource');
    }
    return true;
}
