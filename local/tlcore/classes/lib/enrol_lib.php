<?php

namespace local_tlcore\lib;

use stdClass;
use context_course;
use context_system;
use moodle_database;
use enrol_manual_plugin;

require_once($CFG->dirroot.'/enrol/manual/lib.php');

class enrol_lib {

    /**
     * Enrol user on a course.
     *
     * @param int $course_id Moodle course to enrol on
     * @param int $user_id Moodle user to enrol
     * @param int|null $role Optional role shortname to enrol user with, defaults to student
     * @param int|null $start Optional start of enrolment, defaults to now
     * @param int|null $end Optional end of enrolment, defaults to now + 365 days
     * @return stdClass|null user_enrolments record on success, null if no enrolment
     */
    public static function enrol($course_id, $user_id, $role = null, $start = null, $end = null) {
        $start = $start ?: time();

        /** @todo default enrolment duration should be defined in config */
        $end = $end ?: time() + (365 * 86400);

        /** @todo default role should be defined in config */
        $role_id = self::get_role_id_by_shortname($role ?: 'student');

        $enrolment = self::get_enrolment($course_id, $user_id);
        if (is_object($enrolment)) {
            /** @todo enrolment exists, modify here */
            return;
        }

        $instance = self::get_enrolment_instance($course_id);
        $plugin = new enrol_manual_plugin();
        $plugin->enrol_user($instance, $user_id, $role_id, $start, $end);

        $enrolment = self::get_enrolment($course_id, $user_id);
        return $enrolment;
    }

    /**
     * Convenience method to get a role ID by a role shortname.
     *
     * @param string $shortname
     * @return int|null
     */
    public static function get_role_id_by_shortname($shortname) {
        global $DB; /** @var moodle_database $DB */
        $record = $DB->get_field('role', 'id', ['shortname' => $shortname]);
        return $record ?: null;
    }

    /**
     * Gets the roles assigned to a user in a course.
     *
     * @param int $course_id Moodle course ID
     * @param int $user_id Moodle user ID
     * @return array Role objects, empty if not found
     */
    public static function get_roles_in_course($course_id, $user_id) {
        global $DB; /** @var moodle_database $DB */
        $context = context_course::instance($course_id);
        $q = "SELECT r.* FROM {role} r
            JOIN {role_assignments} ra ON r.id = ra.roleid
            WHERE ra.contextid = ?
              AND ra.userid = ?
        ";
        $records = array_values( $DB->get_records_sql($q, [$context->id, $user_id]) );
        return $records ?: [];
    }

    /**
     * Gets the roles assigned to a user at the system level.
     *
     * @param int $user_id Moodle user ID
     * @return array Role objects (unindexed), empty if not found
     */
    public static function get_roles_in_system($user_id) {
        global $DB; /** @var moodle_database $DB */
        $context = context_system::instance();
        $q = "SELECT r.* FROM {role} r
            JOIN {role_assignments} ra ON r.id = ra.roleid
            WHERE ra.contextid = ?
              AND ra.userid = ?
        ";
        $records = array_values( $DB->get_records_sql($q, [$context->id, $user_id]) );
        return $records ?: [];
    }

    /**
     * True if user is enrolled on course.
     *
     * @param int $course_id Moodle course ID to check
     * @param int $user_id Moodle user ID
     * @return boolean True if enrolled on course
     */
    public static function is_user_enrolled($course_id, $user_id) {
        return (bool)self::get_enrolment($course_id, $user_id);
    }

    /**
     * Modify an existing enrolment.
     *
     * @param int $course_id Moodle course ID
     * @param int $user_id Moodle user Id
     * @param int|null $role_id Optional role ID (unimplemented)
     * @param int|null $start Optional enrol start
     * @param int|null $end Optional enrol end
     * @return stdClass|null
     */
    public static function modify($course_id, $user_id, $role_id = null, $start = null, $end = null) {
        $enrolment = self::get_enrolment($course_id, $user_id);
        $instance = self::get_enrolment_instance($course_id);
    
        if ($enrolment) {
            $start or $start = $enrolment->timestart;
            $end or $end = $enrolment->timeend;
        } else {
            $start or $start = 0;
            $end or $end = 0;
        }
    
        $plugin = new enrol_manual_plugin();
        $plugin->update_user_enrol($instance, $user_id, null, $start, $end);
        $enrolment = self::get_enrolment($course_id, $user_id);
        return $enrolment;
    }

    /**
     * Unenrols user from course.
     *
     * @param int $course_id Moodle course ID
     * @param int $user_id Moodle user ID
     * @return bool True if unenrolled
     */
    public static function unenrol($course_id, $user_id) {
        $instance = $this->get_enrolment_instance($course_id);
        $plugin = new enrol_manual_plugin();
        $result = $plugin->unenrol_user($instance, $user_id);
    
        return (bool)$result;
    }

    /**
     * Gets a Moodle user_enrolments record.
     * Used for determining whether a user is enrolled.
     *
     * @param int $course_id Moodle course ID
     * @param int $user_id Moodle user ID
     * @return stdClass|null user_enrolments record on success, null if no enrolment
     */
    protected static function get_enrolment($course_id, $user_id) {
        global $DB; /** @var moodle_database $DB */

        $instance = self::get_enrolment_instance($course_id);
    
        if (!isset($instance->id) || !$instance->id) {
            return;
        }
    
        $record = $DB->get_record('user_enrolments', [
            'enrolid' => $instance->id,
            'userid' => $user_id
        ]);

        return $record ?: null;
    }

    /**
     * Gets an enrolment instance, related to course ID of enrolment.
     * Needed for user_enrolments record to function properly.
     *
     * @param int $course_id Moodle course ID
     * @return stdClass|null enrolment instance record, null if not found
     */
    protected static function get_enrolment_instance($course_id) {
        global $DB; /** @var moodle_database $DB */

        $instance = $DB->get_record('enrol', [
            'courseid' => $course_id,
            'enrol' => 'manual'
        ], '*');

        return $instance ?: null;
    }
}