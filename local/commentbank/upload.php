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
 * A form for commentbank upload.
 *
 * @package    core_commentbank
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->dirroot.'/commentbank/upload_form.php');
require_once($CFG->libdir . '/csvlib.class.php');

$contextid = optional_param('contextid', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_URL);

require_login();

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}
if ($context->contextlevel != CONTEXT_COURSECAT && $context->contextlevel != CONTEXT_SYSTEM) {
    print_error('invalidcontext');
}

$PAGE->set_context($context);
$baseurl = new moodle_url('/commentbank/upload.php');
$PAGE->set_url($baseurl);
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_pagelayout('admin');

navigation_node::override_active_url(new moodle_url('/commentbank/index.php', array()));


$uploadform = new commentbank_upload_form(null, array('contextid' => $context->id, 'returnurl' => $returnurl));

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/commentbank/index.php', array('contextid' => $context->id));
}

if ($uploadform->is_cancelled()) {
    redirect($returnurl);
}

$strheading = get_string('uploadcomment', 'commentbank');
$PAGE->navbar->add($strheading);

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help($strheading, 'uploadcomments', 'commentbank');

if ($editcontrols = commentbank_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}

if ($data = $uploadform->get_data()) {
    $commentbanksdata = $uploadform->get_commentbank_data();
    foreach ($commentbanksdata as $commentbank) {
        commentbank_add_commentbank($commentbank);
    }
    echo $OUTPUT->notification(get_string('uploadedcomments', 'commentbank', count($commentbanksdata)), 'notifysuccess');
    echo $OUTPUT->continue_button($returnurl);
} else {
    $uploadform->display();
}

echo $OUTPUT->footer();

