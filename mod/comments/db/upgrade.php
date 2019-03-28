<?php

function xmldb_comments_upgrade($oldeversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019032800) {

        // Define table comments_likes to be created.
        $table = new xmldb_table('comments_likes');

        // Adding fields to table comments_likes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('postid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table comments_likes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table comments_likes.
        $table->add_index('post', XMLDB_INDEX_NOTUNIQUE, array('postid'));

        // Conditionally launch create table for comments_likes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Comments savepoint reached.
        upgrade_mod_savepoint(true, 2019032800, 'comments');
    }
}