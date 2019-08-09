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

/** LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
if (!defined('MOODLE_INTERNAL')) {
    die(get_string('nodirectaccess','block_learnerscript'));    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');

class roleincourse_form extends moodleform {

    function definition() {
        global $DB, $USER, $CFG;

        $mform = & $this->_form;

        $mform->addElement('header', 'crformheader', get_string('roleincourse', 'block_learnerscript'), '');

        $roles = $DB->get_records('role');
        $userroles = array();
        foreach ($roles as $r) {
            if ($r->shortname == 'manager') {
                continue;
            } else {
                switch ($r->shortname) {
                    case 'coursecreator':   $userroles[$r->id] = get_string('coursecreators'); break;
                    case 'editingteacher':  $userroles[$r->id] = get_string('defaultcourseteacher'); break;
                    case 'teacher':         $userroles[$r->id] = get_string('noneditingteacher'); break;
                    case 'student':         $userroles[$r->id] = get_string('defaultcoursestudent'); break;
                    case 'guest':           $userroles[$r->id] = get_string('guest'); break;
                    case 'user':            $userroles[$r->id] = get_string('authenticateduser'); break;
                    case 'frontpage':       $userroles[$r->id] = get_string('frontpageuser', 'role'); break;
                    // We should not get here, the role UI should require the name for custom roles!
                    default:                $userroles[$r->id] = $r->shortname; break;
                }
            }
        }
        // $systemcontext = context_system::instance();
        // $roles = get_switchable_roles($systemcontext);
        $mform->addElement('select', 'roleid', get_string('roles'), $userroles);


        // buttons
        $this->add_action_buttons(true, get_string('add'));
    }

}
