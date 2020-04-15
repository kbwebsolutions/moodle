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
 * Add post or comments to the activity
 *
 * @package     mod_activitycomments
 * @copyright   2020 Kieran Briggs <kbriggs@chartered.college>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class mod_activitycomment_comment_form extends moodleform {

    /**
     * Form Definition
     *
     * @return void
     *
     */
    protected function definition() {

        $mform = & $this->_form;
        $postid = $this->_customdata['postid'];
        $moduleid = $this->_customdata['moduleid'];
        $replycommentid = $this->_customdata['replycommentid'];

        $textareaparams = array('class' => 'tl-commenttext', 'id' => 'commenttext_' . $postid.'_'.$replycommentid, 'rows' => '3', 'cols' => '65');
        $mform->addElement('textarea', 'text', '', $textareaparams);
        $mform->setType('text', PARAM_TEXT);

        $mform->addElement('hidden', 'postid', $postid);
        $mform->setType('postid', PARAM_INT);

        $mform->addElement('hidden', 'moduleid', $moduleid);
        $mform->setType('moduleeid', PARAM_INT);

        $mform->addElement('hidden', 'action', 'postcomment');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'replycommentid', $replycommentid);
        $mform->setType('replycommentid', PARAM_INT);

        // Post button
        $params = array('class' => 'tl-postcomment', 'id' => 'postcomment_' . $postid.'_'.$replycommentid);
        $mform->addElement('submit', 'submitcomment', get_string('postcomment', 'activitycomments'), $params);

    }
}