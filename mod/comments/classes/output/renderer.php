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
 * This file contains a custom renderer class used by the forum module.
 *
 * @package   mod_comments
 * @copyright 2019 Kieran Briggs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_comments\output;

defined('MOODLE_INTERNAL')||die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base
{

    public function render_comment_block($data)
    {
        global $DB, $USER, $output;


        $code = '<div id="comment-posts"><ul class="feed">';

        foreach ($data as $post) {

            If ($post->userid === $USER->id) {

            }
            $liked = checked_liked($post->id, $USER->id);
            $user = $DB->get_record('user', array('id' => $post->userid));
            $userpix = $output->user_picture($user);

            $code .= '<li id=' . $post->id . ' class="item"><div class=' . $userpix . '</div>';
            $code .= '<div class="msg-body"><div class="header">';
            $code .= '<div class="name">' . $post->firstname . ' ' . $post->lastname . '</div>';
            $code .= '<div class="date">' . date("d F", $post->created) . '</div></div>';
            $code .= '<div class="message">' . $post->message . '</div>';
            If ($post->userid === $USER->id) {
                $code .= '<div class="options"><a class="likes" href="#">Likes</a><a class="delete" href="#">Delete</a></div></div>';
            } else {
                $code .= '<div class="options"><a class="likes" href="#">Likes</a></div></div>';
            }
            $code .= '</li>';
        }

        echo '</div>';

        return $code;
    }

}