<?php

/**
 * Renderer for outputting the mypdchat course format.
 *
 * @package format_mypdchat
 * @copyright 2014 Andreas Wagner, Synergy Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/activitycomments/forms/comment_form.php');

class mod_activitycomments_renderer extends plugin_renderer_base {
    public function print_page() {

        $commentform = new \mod_activitycomment_comment_form();
        $commentform->display();
    }

}