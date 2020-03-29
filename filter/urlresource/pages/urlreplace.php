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
require_once(dirname(__FILE__) . '../../../../config.php');
require_once($CFG->dirroot . '/filter/urlresource/lib.php');
require_once($CFG->dirroot . '/filter/urlresource/pages/urlreplaceedit_form.php');

$cmid = required_param('cmid', PARAM_INT);
$url = required_param('url', PARAM_URL);

$cm = get_coursemodule_from_id('url', $cmid, 0, false, MUST_EXIST);
$urlmodule = $DB->get_record('url', array('id' => $cm->instance), '*', MUST_EXIST);

$externalurl = $urlmodule->externalurl;

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_course_login($course);

// ...start setting up the page.
$context = context_course::instance($course->id, MUST_EXIST);
require_capability('filter/urlresource:editallurls', $context);

$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/filter/urlresource/pages/urlreplace.php', array('cmid' => $cmid)));
$PAGE->set_title(get_string('urlreplaceedit', 'filter_urlresource'));
$PAGE->set_heading(get_string('urlreplaceedit', 'filter_urlresource'));

$urlresourcehelper = filter_url_resource_helper::instance($course->id);
$urlreplacedata = $urlresourcehelper->get_urlreplacedata($url, $externalurl);

$urlreplaceeditform = new urlreplaceedit_form(null,
                array(
                    'cmid' => $cmid,
                    'course' => $course,
                    'url' => $url, // ...url to save.
                    'externalurl' => $externalurl,
                    'urlreplacedata' => $urlreplacedata,
                    'urlimgs' => $urlresourcehelper->grab_imageurls_from_webpage($externalurl)
        ));

if ($urlreplaceeditform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
}

if ($data = $urlreplaceeditform->get_data()) {

    $result = $urlresourcehelper->save_urlreplace($data);

    if ($result['error'] == 0) {

        $redirect = new moodle_url('/course/view.php?id=' . $course->id);
        redirect($redirect, get_string($result['message'], 'filter_urlresource'));
    } else {

        $msg = get_string($result['message'], 'filter_urlresource');
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('urlreplaceedit', 'filter_urlresource'));
if (!empty($msg)) {
    echo $OUTPUT->notification(get_string($msg, 'filter_urlresource'));
}

$urlreplaceeditform->display();

echo $OUTPUT->footer();