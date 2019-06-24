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


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
use local_commentbank\lib\comment_lib;
global $CFG, $PAGE;


$id = optional_param('id', '', PARAM_INT);
$rowid = optional_param('rowid', '', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA); // Edit, addnew or delete.

$id = $id  ?: 1;

require_login();

global $USER;

$PAGE->set_context(context_course::instance($id));
$PAGE->navigation->find($id, navigation_node::TYPE_COURSE)->make_active();
$PAGE->set_url('/local/commentbank/index.php');
if (!has_capability('local/commentnbank:create_site_comments', $PAGE->context)) {
    echo $OUTPUT->header();
    echo(get_string('nopermission', 'local_commentbank'));
    echo $OUTPUT->footer();
    return;
}

class local_commentbank_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $id = $this->_customdata['id'];
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'addnew');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('submit', 'addnewcomment', get_string("addnewcomment", 'local_commentbank'));

    }
}

$mform = new local_commentbank_form(null, ['id' => $id]);

class local_addnewcomment_form extends moodleform {

    protected function definition() {
        global $PAGE, $DB;
        $mform = $this->_form;
        $rowid = $this->_customdata['rowid'];
        $action = $this->_customdata['action'];

        $mform = $this->_form;
        $context = [
            CONTEXT_SYSTEM => get_string('system','local_commentbank'),
            CONTEXT_COURSECAT => get_string('coursecategory','local_commentbank'),
            CONTEXT_COURSE => get_string('course','local_commentbank'),
        ];
        list($courses, $coursecategories) = get_course_data();
        $options = [
            'multiple' => false,
            'noselectionstring' => 'No selection',
            ];
        $options = [];

        if ($action == 'delete') {
            $headertext = get_string('warningheader', 'local_commentbank');
            $mform->addElement('header', 'warningheader', $headertext);
            /* Stops headers being collapsible */
            $mform->setDisableShortForms(true);
            $mform->addHelpButton('warningheader', 'deletecomment', 'local_commentbank');
        }

        $mform->addElement('select', 'context', 'Context', $context);

        $mform->setType('context', PARAM_INT);
        $mform->addElement('html', '<div id="autoselects" class="hidden">');
        $mform->addElement('searchableselector', 'course', 'Courses', $courses, $options);
        $mform->addElement('searchableselector', 'coursecategory', 'Course Categories', $coursecategories, $options);
        $mform->addElement('html', '</div>');

        $mform->addElement(
            'textarea',
            'commenttext',
            get_string('commenttext', 'local_commentbank'),
            ['cols' => 60, 'rows' => 2]
         );
         $mform->setType('commenttext', PARAM_NOTAGS);
         $mform->setType('course', PARAM_NOTAGS);
         $mform->setType('coursecategory', PARAM_NOTAGS);

        if ($action == 'addnew') {
            $mform->addElement('hidden', 'action', 'addnew');
            $mform->setType('action', PARAM_ALPHA);
            $params = ['action' => $action, 'instanceid' => '', 'contextlevel' => ''];
            $mform->addRule('commenttext', get_string('required'), 'required', '', 'client');
            $PAGE->requires->js_call_amd('local_commentbank/add_comment', 'init', $params);
        }
        if ($action == '') {
            $mform->addElement('hidden', 'action', $action);
            $mform->setType('action', PARAM_ALPHA);
        }
        $records = [];
        $records = $DB->get_record('local_commentbank', ['id' => $rowid]);
        if ($action == 'edit' || $action == 'addnew') {
            $mform->addHelpButton('context', 'contextselect', 'local_commentbank');
            $this->add_action_buttons();
        }
        if ($action == 'delete') {
            $this->add_delete_elements($mform, $rowid, $records);
            $submitlabel = get_string('delete');
            $this->add_action_buttons(true, $submitlabel);
        }
        if ($action == 'edit') {
            $this->add_edit_elements($mform, $rowid, $records);
        }
    }
    public function validation($fromform, $data) {
        $errors = array();
        if(($fromform['context'] == CONTEXT_COURSE) && (!is_numeric($fromform['course']))){
            $errors['commenttext'] =get_string('nocourseselected','local_commentbank');
        }
        if(($fromform['context'] == CONTEXT_COURSECAT) && (!is_numeric($fromform['coursecategory']))){
            $errors['commenttext'] =get_string('nocoursecategoryselected','local_commentbank');
        }
        if ($errors) {
            return $errors;
        } else {
            return true;
        }
    }
    public function add_edit_elements($mform, $rowid, $result) {
        global $PAGE;
        $params = ['action' => 'edit', 'instanceid' => $result->instanceid, 'contextlevel' => $result->contextlevel];
        $mform->addRule('commenttext', get_string('required'), 'required', '', 'client');
        $PAGE->requires->js_call_amd('local_commentbank/add_comment', 'init', $params);

        if ($result) {
            $mform->setDefault('context', $result->contextlevel);
            if ($result->contextlevel == CONTEXT_COURSECAT) {
                $mform->setDefault('coursecategory', $result->instanceid);
            } else if ($result->contextlevel == CONTEXT_COURSE) {
                $mform->setDefault('course', $result->instanceid);
            }

            $mform->setDefault('commenttext', $result->commenttext);
            $mform->addElement('hidden', 'rowid', $rowid);
            $mform->setType('rowid', PARAM_INT);
            $mform->addElement('hidden', 'contextlevel', $result->contextlevel);
            $mform->addElement('hidden', 'authoredby', $result->authoredby);
            $mform->setType('contextlevel', PARAM_INT);
            $mform->setType('authoredby', PARAM_INT);
            $mform->addElement('hidden', 'action', 'edit');
            $mform->setType('action', PARAM_ALPHA);
        }

    }
    public function add_delete_elements($mform, $rowid, $result) {
        global $PAGE;
        $mform->setType('action', PARAM_ALPHA);
        $mform->setDefault('commenttext', $result->commenttext);
        $mform->freeze('commenttext');
        $mform->freeze('context');
        $mform->addElement('hidden', 'rowid', $result->id);

        $mform->setType('rowid', PARAM_INT);
        $params = ['action' => 'delete', 'instanceid' => $result->instanceid, 'contextlevel' => $result->contextlevel];
        $PAGE->requires->js_call_amd('local_commentbank/add_comment', 'init', $params);

        if ($result) {
            $mform->setDefault('context', $result->contextlevel);
            if ($result->contextlevel == CONTEXT_COURSECAT) {
                $mform->setDefault('coursecategory', $result->instanceid);
                $mform->freeze('coursecategory');
            } else if ($result->contextlevel == CONTEXT_COURSE) {
                $mform->setDefault('course', $result->instanceid);
                $mform->freeze('course');
            }
        }
        $mform->addElement('hidden', 'action', 'delete');
        $mform->setType('action', PARAM_INT);
    }

}


/**
 * Get data for all courses and course categories for the whole site. There may
 * be a way of getting a cached version of this, though this form will not see
 * a huge amount of traffic, so performance should not be an issue.
 */
function get_course_data(): array {
    global $DB;
    $records = $DB->get_records_sql('select id, fullname from {course} where id > 1');
    foreach ($records as $record) {
        $courses[$record->id] = $record->fullname;
    }
    $records = $DB->get_records('course_categories', null, null, 'id,name');
    foreach ($records as $record) {
        $coursecategories[$record->id] = $record->name;
    }
    return [$courses, $coursecategories];
}
$PAGE->requires->js_call_amd('local_commentbank/bank_datatable', 'init');

/**
 * get instance (id field) according to the context.
 * The courseid if it is context course, coursecategory id
 * if it is coursecategory and blank if it is system (there
 * is only the one system so instance is meaningless)
 *
 * @param array $data
 * @return int
 */
function get_instance(stdClass $data) :int {
    if ($data->context == CONTEXT_SYSTEM) {
        $instanceid = 0;
    } else if ($data->context == CONTEXT_COURSECAT) {
        $instanceid = (int) $data->coursecategory;
    } else if ($data->context == CONTEXT_COURSE) {
        $instanceid = (int) $data->course;
    }
    return $instanceid;
}

$newcomment = new local_addnewcomment_form(null, ['id' => $id, 'rowid' => $rowid, 'action' => $action]);

if ($newcomment->is_cancelled()) {
    unset($_POST);
    redirect(new moodle_url('/local/commentbank/index.php'));
} else if ($data = $newcomment->get_data()) {
    if ($action == 'addnew') {
        $instanceid = get_instance($data);
        comment_lib::add_comment($data->commenttext, $data->context, $USER->id, $instanceid);
        redirect(new moodle_url('/local/commentbank/index.php'));
    }
    if ($action == 'edit') {
        $instanceid = (int)get_instance($data);
        comment_lib::update_comment($data->rowid, $data->commenttext, $data->context, $USER->id, $instanceid);
        redirect(new moodle_url('/local/commentbank/index.php'));
    }
    if ($action == 'delete') {
        comment_lib::delete_comment($data->rowid);
        \core\notification::info(get_string('deletioncomplete', 'local_commentbank'));
        redirect(new moodle_url('/local/commentbank/index.php'));
    }
}
if ($action !== '') {
    echo $OUTPUT->header();
    $newcomment->display();
    echo $OUTPUT->footer();
}


if ($action == '') {
    if ($mform->is_cancelled()) {
        unset($_POST);
        redirect(new moodle_url('/local/commentbank/index.php'));
    } else if ($data = $mform->get_data()) {
        if (isset($data->addnewcomment) || isset($data->editcomment)) {
            echo $OUTPUT->header();
            $newcomment->display();
            echo $OUTPUT->footer();
        }
    } else {
        echo $OUTPUT->header();
        $table = <<<TEMP
        <div id="local_commentbank_vue">
            <commentbank-main></commentbank-main>
        </div>
TEMP;
        $mform->display();
        echo ($table);
        echo $OUTPUT->footer();
    }
}