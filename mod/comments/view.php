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
 * Prints an instance of mod_comments.
 *
 * @package     mod_comments
 * @copyright   2019 Kieran Briggs <kbriggs@chartered.college>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

global $DB, $OUTPUT, $USER;


// Course_module ID
$id = required_param('id', PARAM_INT);


list ($course, $cm) = get_course_and_cm_from_cmid($id, 'comments');


require_login($course, true, $cm);
$context = context_module::instance($cm->id);

/*$event = \comments\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('comments', $moduleinstance);
$event->trigger();*/

$PAGE->set_url('/mod/comments/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cm->name));
$PAGE->set_heading(format_string($course->fullname));
$output = $PAGE->get_renderer('mod_comments');
$pageurl = $PAGE->url;

echo $output->header();


$mform = new mod_comments_message_form($pageurl, array('modid'=>$cm->id));


$mform->display();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/comments/view.php', array('id' => $mformdata->id)));
} else if($fromform = $mform->get_data()) {
    $todb = new stdClass();
    $todb->commentsid = $cm->id;
    $todb->userid = $USER->id;
    $todb->created = time();
    $todb->message = $fromform->posting;
    $todb->deleted = 0;
    $DB->insert_record('comments_posts', $todb);
    //redirect(new moodle_url('/mod/comments/view.php', array('id' => $fromform->id)));
};


$posts = get_comments($cm->id);
$commentposts = new \mod_comments\output\comment_posts($posts);
echo $output->render_comment_posts($commentposts);
//echo $output->render_comment_posts($posts);

echo $output->footer();
