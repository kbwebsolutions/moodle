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
 * Library of interface functions and constants.
 *
 * @package     mod_comments
 * @copyright   2019 Kieran Briggs <kbriggs@chartered.college>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function mod_comments_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}


/**
 * Get comments from database
 *
 * @param the module id
 * @return array of comments in database for given module id
 */
function get_comments($cmid) {
    global $DB;


    $sql = "SELECT cp.id, cp.created, cp.userid, cp.message, u.firstname, u.lastname
            FROM {comments_posts} cp, {user} u
            WHERE u.id = cp.userid 
               AND cp.deleted = :del
               AND cp.commentsid = :id
            ORDER BY cp.created DESC";

    $comments = $DB->get_records_sql($sql, array('del'=>'0', 'id'=>$cmid));

    return $comments;
}

/**
 * Saves a new instance of the mod_comments into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_comments_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function comments_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = time();

    $id = $DB->insert_record('comments', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_comments in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_comments_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function comments_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('comments', $moduleinstance);
}

/**
 * Removes an instance of the mod_comments from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function comments_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('comments', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('comments', array('id' => $id));

    return true;
}

/**
 * Checks to see if the current user has liked the post
 * 
 * @param 
 */
function checked_liked($postid, $user) {
    global $DB;

    $liked = $DB->get_record('comments_likes', array('postid' => $postid, 'userid' => $user));

}
