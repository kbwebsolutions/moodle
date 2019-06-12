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
 * Class containing data for message posts
 *
 * @package    local_hackfest
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_comments\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Class containing data for message posts
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_posts implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;

        $sql = "SELECT cp.id, cp.created, cp.userid, cp.message, u.firstname, u.lastname
          FROM {comments_posts} cp, {user} u
         WHERE u.id = cp.userid 
               AND cp.deleted = :del
               AND cp.commentsid = :id
      ORDER BY cp.created DESC";

        $posts = $DB->get_recordset_sql($sql, array('del'=>'0', 'id'=>$cm->id));

        $data = new stdClass();

        foreach ($posts as $p) {
            $messages[] = array (
                "id" => $p->id,
                "username" => $p->firstname. ' '. $p->lastname,
                "date" => date("d F", $p->created),
                "message" => $p->message
            );
        }

        // TODO export some stuff.

        return $data;
    }
}