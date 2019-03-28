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
$id = optional_param('id', 0, PARAM_INT);
$id = 18;

// comments activity instance ID.
$c  = optional_param('c', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('comments', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('comments', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($c) {
    $moduleinstance = $DB->get_record('comments', array('id' => $c), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('comments', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'comments'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

/*$event = \comments\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('comments', $moduleinstance);
$event->trigger();*/

$PAGE->set_url('/mod/comments/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$output = $PAGE->get_renderer('mod_comments');
$page = new \mod_comments\output\comment_posts();
$pageurl = $PAGE->url;

echo $OUTPUT->header();
echo format_module_intro('comments', $moduleinstance, $id);
 
$mform = new mod_comments_message_form();

$mform->display();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/comments/view.php', array('id' => $cm->id)));
} else if($fromform = $mform->get_data()) {
    $todb = new stdClass();
    $todb->commentsid = $cm->id;
    $todb->userid = $USER->id;
    $todb->created = time();
    $todb->message = $fromform->posting;
    $todb->deleted = 0;
    //print_r($todb); 
    $DB->insert_record('comments_posts', $todb);
    redirect(new moodle_url('/mod/comments/view.php', array('id' => $cm->id)));
};

echo "<hr />";

$sql = "SELECT cp.id, cp.created, cp.userid, cp.message, u.firstname, u.lastname
          FROM {comments_posts} cp, {user} u
         WHERE u.id = cp.userid 
               AND cp.deleted = :del
               AND cp.commentsid = :id
      ORDER BY cp.created DESC";

$posts = $DB->get_recordset_sql($sql, array('del'=>'0', 'id'=>$cm->id));
echo '<div id="comment-posts"><ul class="feed">';
foreach ($posts as $post) {

    //render_from_template($message_list, $post);
    //echo $output->render($page);
    If ($post->userid === $USER->id) {

    }
    $liked = checked_liked($post->id, $USER->id);
    $user = $DB->get_record('user', array('id' => $post->userid));
    $userpix = $OUTPUT->user_picture($user);
    $code = '<li id='.$post->id.' class="item"><div class="user">'.$userpix.'</div>';
    $code .= '<div class="msg-body"><div class="header">';
    $code .= '<div class="name">'.$post->firstname.' '.$post->lastname.'</div>';
    $code .= '<div class="date">'.date("d F", $post->created).'</div></div>';
    $code .= '<div class="message">'.$post->message.'</div>';
    If ($post->userid === $USER->id) {
        $code .= '<div class="options"><a class="option" href="#"><img src="'.$CFG->wwwroot.'/mod/comments/pix/like.svg" height="20" width="20" /> Like</a><a class="option" href="#"><img src="'.$CFG->wwwroot.'/mod/comments/pix/garbage.svg" height="20" width="20" />Delete</a></div></div>';
    } else {
        $code .= '<div class="options"><a href="#"><img src="'.$CFG->wwwroot.'/mod/comments/pix/like.svg" height="20" width="20" /> Like</a></div></div>';
    }
        $code .= '</li>';
    echo $code;
}
$posts->close();
echo '</div>';

echo $OUTPUT->footer();
