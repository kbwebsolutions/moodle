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

/**
 * A custom renderer class that extends the plugin_renderer_base and
 * is used by the forum module.
 *
 * @package   mod_comments
 * @copyright 2019 Kieran Briggs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

namespace mod_comments\output;

defined('MOODLE_INTERNAL')||die();

use plugin_renderer_base;


class renderer extends plugin_renderer_base
{

    public function render_comment_posts($post)
    {
        $data = $post->export_for_template($this);
        return $this->render_from_template('mod_comments/comment_posts', $data);
    }
}