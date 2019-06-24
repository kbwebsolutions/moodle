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
 * Run the code checker from the web.
 *
 * @package    local_commentbank
 * @copyright  2019 Titus Learning by Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

 
global $CFG, $PAGE;
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_login();


global $USER;
$id = optional_param('id', '', PARAM_RAW);
$id = $id  ?: 1;

$PAGE->set_context(context_course::instance($id));
$PAGE->navigation->find($id, navigation_node::TYPE_COURSE)->make_active();
$PAGE->set_url('/local/commentbank/add_newcomment_form.php');
class local_addnewcomment_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        // $id= $this->_customdata['id'];
        $mform->addElement(
            'text',
            'comment',
            get_string('comment', 'local_commentbank'),
            ['size' => 50, 'rows' => 4]
        );
        $buttons[] = $mform->createElement('submit', 'save', 'Save');
        $buttons[] = $mform->createElement('button', 'cancel', 'Cancel');
        $mform->addGroup($buttons);

    }
}

// if ($mform->is_cancelled()) {
//     $commentbank = new local_commentbank_form();
        
//     echo $OUTPUT->header();
//     $commentbank->display();
//     echo $OUTPUT->footer();

//    // redirect(new moodle_url('/local/tlactionplans/register_form.php'));
// } else if ($data = $mform->get_data()) {
//     $commentbank = new local_commentbank_form();
    
//     echo $OUTPUT->header();
//     $commentbank->display();
//     echo $OUTPUT->footer();

// } else {
//     //Set default data (if any)
//     //$mform->set_data($toform);
//     $mform = new local_addnewcomment_form(null,['id'=>$id]);
//     echo $OUTPUT->header();
//     $mform->display();
//     echo $OUTPUT->footer();
//   }