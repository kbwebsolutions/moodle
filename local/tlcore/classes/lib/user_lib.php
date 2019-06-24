<?php

namespace local_tlcore\lib;

use stdClass;
use moodle_database, moodle_exception;
use context_user, context_system;
use user_crete_user;

require_once($CFG->dirroot.'/local/tlcore/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/lib/gdlib.php'); // for picture processing

class user_lib {
    public static function create_api_user($username = 'api') {
        global $CFG, $DB; /** @var moodle_database $DB */

        // if user exists, don't do anything 
        if ($DB->record_exists('user', ['username' => $username])) {
            return;
        }

        // turn on webservices
        set_config('enablewebservices', true);

        // if no protocols, enable rest
        $protocols = $CFG->webserviceprotocols;
        if (empty($protocols)) set_config('webserviceprotocols', 'rest');

        // create the user
        $user = new stdClass();
        $user->username = $username;
        $user->firstname = 'Api';
        $user->lastname = 'User';
        $user->email = "{$username}@localhost";
        $user->confirmed = true;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->id = user_create_user($user);

        $context = context_system::instance();
        $role_id = $DB->get_field('role', 'id', ['shortname' => 'webservices']);
        if (!$role_id) {
            $role_id = create_role('Web services', 'webservices', 'Web services role');
            assign_capability('webservice/rest:use', CAP_ALLOW, $role_id, $context->id);
        }

        role_assign($role_id, $user->id, $context->id);

        return $user;
    }

    /**
     * Create a Moodle user.
     * This wraps around Moodle's standard create methods.
     *
     * @param string $firstname User firstname
     * @param string $lastname User lastname
     * @param string $email Optional user email
     * @param string $username Optional user name
     * @param string $password Optional password
     * @return stdClass New user record
     */
    public static function create_user($firstname, $lastname, $email = '', $username = '', $password = '') {
        global $CFG, $DB; /** @var moodle_database $DB */

        /** @todo More advanced username patterns */
        $username = $username ?: strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstname.$lastname));
        $new_username = $username;
        $i = 1;

        while ($DB->record_exists('user', ['username' => $new_username])) {
            $new_username = $username.(string)$i++;
        }

        $email = $email ?: "{$new_username}@localhost";

        // create the user object
        $user = new stdClass();
        $user->username = $new_username;
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->emailstop = empty($email) ? 1 : 0;
        $user->confirmed = true;
        $user->mnethostid = $CFG->mnet_localhost_id;

        if ($password) {
            $user->password = $password;
        }

        $user->id = user_create_user($user);
        return $user;
    }

        /**
     * Gets the value of a custom field by its shortname.
     *
     * @param int $moodle_id User ID
     * @param string $field_id Field shortname to fetch
     * @return mixed|null Value of custom field, null if not found or empty
     */
    public static function get_custom_field($moodle_id, $field_shortname) {
        $fields = self::get_custom_fields($moodle_id);
        if (isset($fields[$field_shortname])) {
            return $fields[$field_shortname];
        }
    }

    /**
     * Gets custom fields for given Moodle user ID.
     *
     * @param int $moodle_id User ID to get fields
     * @return array Array of fields, empty if not found
     */
    public static function get_custom_fields($moodle_id) {
        global $DB; /** @var moodle_database $DB */
        $fields = [];

        $q = "SELECT id, shortname FROM {user_info_field}";
        $keys = $DB->get_records_sql($q);
        
        $q = "SELECT * FROM {user_info_data} WHERE userid = ?";
        $values = $DB->get_records_sql($q, [$moodle_id]);

        foreach ($keys as $key) {
            $fields[$key->shortname] = null; // initialise all custom fields
        }
        foreach ($values as $value) {
            $fields[$keys[$value->fieldid]->shortname] = $value->data;
        }

        return $fields;
    }

    public static function get_system_roles($user_id) {
        global $DB; /** @var moodle_database $DB */
        $context = context_system::instance();

        $q = "SELECT DISTINCT r.* FROM {role_assignments} ra
        JOIN {role} r ON ra.roleid = r.id
          WHERE ra.userid = ?
            AND ra.contextid = ?
        ";

        $params = [$user_id, $context->id];
        $records = $DB->get_records_sql($q, $params);

        return $records;
    }

    /**
     * Gets user ID by a custom field value.
     *
     * @param string $field Custom field to use
     * @param string $value Value to match
     * @return stdClass|null Moodle user ID object, null if not found
     */
    public static function get_user_by_custom_field($field, $value) {
        global $DB; /** @var moodle_database $DB */

        $field_id = $DB->get_field('user_info_field', 'id', ['shortname' => $field]);
        if (!$field_id) return false;

        $q = "SELECT u.id FROM {user} u
                JOIN {user_info_data} ud ON ud.userid = u.id
                WHERE ud.fieldid = ?
                  AND ud.data = ?
        ";
        $params = [$field_id, $value];
        $records = $DB->get_fieldset_sql($q, $params);
        if (!$records) {
            return null;
        }
        $user_id = reset($records); /** @todo handle if there's more than one user with same ID */

        $user = $DB->get_record('user', ['id' => $user_id], '*', MUST_EXIST);
        return $user;
    }
        
    /**
     * Updates a custom field for a Moodle user.
     *
     * @param int $moodle_id Moodle user ID
     * @param string $field_shortname Field to update
     * @param mixed $value Value to set custom field to
     * @return bool True on success
     */
    public static function set_custom_field($moodle_id, $field_shortname, $value) {
        global $DB; /** @var moodle_database $DB */
        
        $field_id = $DB->get_field('user_info_field', 'id', ['shortname' => $field_shortname]);
        if (!$field_id) return false;

        // can't be null
        if (is_null($value)) $value = '';

        if ($record = $DB->get_record('user_info_data', [
            'userid' => $moodle_id,
            'fieldid' => $field_id
        ])) {
            $record->data = $value;
            $DB->update_record('user_info_data', $record);
        } else {
            $DB->insert_record('user_info_data', (object)[
                'userid' => $moodle_id,
                'fieldid' => $field_id,
                'data' => $value
            ]);
        }

        return true;
    }

    /**
     * Updates a set of custom fields.
     *
     * @param int $moodle_id Moodle user ID
     * @param array $fields Array of [field_shortname => value]
     * @return bool True on success
     */
    public static function set_custom_fields($moodle_id, array $fields) {
        foreach ($fields as $shortname => $value) {
            self::set_custom_field($moodle_id, $shortname, $value);
        }
        return true;
    }

    /**
     * Sets the system-level role for the given Moodle user.
     *
     * @param int $user_id Moodle user ID
     * @param string $role Optional role, defaults to student
     * @return int|null Assigned role ID on success, null if no op
     */
    public static function set_system_role($user_id, $role = 'student') {
        global $DB; /** @var moodle_database $DB */

        $context = context_system::instance();
        $role_id = enrol_lib::get_role_id_by_shortname($role); // get the role ID of the specified role

        if (!$role_id) {
            throw new moodle_exception('no_role_id', 'local_tlcore');
        }

        // assign user to system role
        $result = null;
        if (!$DB->record_exists('role_assignments', [
            'contextid' => $context->id,
            'userid' => $user_id,
            'roleid' => $role_id
        ])) {
            $result = role_assign($role_id, $user_id, $context->id);
        }

        return $result;
    }

    /**
     * Removes a system-level role from a user.
     *
     * @param int $user_id Moodle ID
     * @param string $role Shortname of role to remove
     * @return bool True on success
     */
    public static function unset_system_role($user_id, $role) {
        $role_id = enrol_lib::get_role_id_by_shortname($role);
        if (!$role_id) {
            return false;
        }

        $context = context_system::instance();
        role_unassign($role_id, $user_id, $context->id);
        return true;
    }
}