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
 * @copyright  2020 Titus Learning by Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_commentbank\lib;


defined('MOODLE_INTERNAL') || die();


class comment_lib {
    /**
     * Add a new comment to the local_commentbank for re-use in passfail rubric grading form
     * (and potentially other places)
     *
     * @param string $comment
     * @param integer $context
     * @param integer $userid
     * @param integer $instanceid
     * @return boolean
     */
    public static function add_comment(string $comment, int $context, int $userid, int $instanceid) :int {
        global $DB;

        $record = (object) [
                'commenttext'  => $comment,
                'contextlevel' => $context,
                'instanceid'   => ($instanceid ?: 0),
                'authoredby'   => $userid,
                'typemodified' => time(),
                'timecreated'  => time()
        ];
        return $DB->insert_record('local_commentbank', $record);
    }

    public static function update_comment(int $rowid, string $commenttext, int $contextlevel, int $userid, int $instanceid) {
        global $DB;
        $record = (object) [
                'id' => $rowid,
                'commenttext'   => $commenttext,
                'contextlevel'  => $contextlevel,
                'instanceid'    => $instanceid,
                'authoredby'    => $userid,
                'typemodified'  => time(),
                'timecreated'   => time()
        ];
        return $DB->update_record('local_commentbank', $record);
    }
    public static function lookup_context(int $contextid){
        $contexts = [
            CONTEXT_SYSTEM => 'System',
            CONTEXT_COURSECAT => 'Course Category',
            CONTEXT_COURSE => 'Course',
            CONTEXT_MODULE => 'Assignment'
        ];
        if (array_key_exists($contextid, $contexts)) {
            return $contexts[$contextid];
        } else {
            return '';
        }
    }
    /**
     * Cannot think of a scenario for this function at the moment
     *
     * @param int $id
     * @return void
     */
    public static function get_comment($id) {
        global $DB;
       $comment = $DB->get_record('local_commentbank', ['id' => $id]);
       if ($comment->contextlevel == CONTEXT_SYSTEM) {
           $comment->instance='';
        } else if ($comment->contextlevel == CONTEXT_COURSECAT) {
           $coursecat = $DB->get_record('course_categories', ['id'=>$comment->instanceid]) ;
           $comment->instance = $coursecat->name;
        } else if ($comment->contextlevel == CONTEXT_COURSE) {
           $course = $DB->get_record('course', ['id'=>$comment->instanceid]) ;
           $comment->instance = $course->fullname;
        } else if ($comment->contextlevel == CONTEXT_MODULE) {
        $course = $DB->get_record('course', ['id'=>$comment->instanceid]) ;
        $comment->instance = $course->fullname;
    }
       return $comment;
    }
    /**
     * return course that an assignment is on
     * based on assignment course module id (cmid)
     *
     * @param integer $cmid
     * @return integer
     */
    public  static function get_assignment_course(int $cmid) {
      global $DB;
      $sql = "
      SELECT c.id, c.shortname FROM {course_modules} cm
      JOIN {course} c ON cm.course = c.id
      WHERE cm.id = :cmid
     ";
      $course = $DB->get_record_sql($sql, ['cmid'=>$cmid]);
      return $course;
    }

    /**
     * Get assignment by id and associated course information
     *
     * @param integer $assigncmid
     * @return stdClass | boolean
     */
    public static function get_assignment(int $assigncmid)  {
      global $DB;
      $sql = "
      SELECT  c.shortname AS coursename, a.name AS assignmentname, c.id as courseid, c.shortname
      FROM {assign} a
      JOIN {course_modules} cm ON a.id=cm.instance
      JOIN {modules} m ON m.id = cm.module
      JOIN {course} c ON c.id = cm.course
      WHERE m.name='Assign' AND cm.id=:assigncmid
      ";
      $assign = $DB->get_record_sql($sql, ['assigncmid'=>$assigncmid]);
      return $assign;
    }
    /**
     * Because commentbank is a local plugin there
     * is no concept of user capabilities like on a course
     * So this function checks if the user has a role
     * on any enrolled course
     *
     * @param string $testrole
     * @param integer $userid
     * @return boolean
     */
    public static function has_role(string $testrole, int $userid) {
      $courses = enrol_get_users_courses($userid, true);
      foreach ($courses as $course) {
          $context =\context_course::instance($course->id);
          $roles = get_user_roles($context, $userid, false);
          foreach ($roles as $role) {
              if ($testrole == $role->shortname) {
                  return true;
              }
          }
      }
      return false;
  }
    /**
    * Get comments for this module. This will return all comments at
    * System level, all for the course category the module is in and
    * all for this specific course.
    *
    * @return array (of objects)
    */
    public static function get_module_comments($courseid = NULL, $assignmentid = NULL) : array {
        global $DB;
        $coursecat = $DB->get_record('course', array('id' => $courseid), 'category')->category;
        $sql = 'select id,commenttext FROM {local_commentbank} WHERE contextlevel = :context_system
                 OR (contextlevel = :context_coursecat AND instanceid = :category)
                 OR (contextlevel = :context_course AND instanceid = :courseid)
                 OR (contextlevel = :context_module AND instanceid = :assignmentid)';

                $params= [
                    'context_system' => CONTEXT_SYSTEM,
                    'context_coursecat' => CONTEXT_COURSECAT,
                    'context_course' => CONTEXT_COURSE,
                    'context_module' => CONTEXT_MODULE,
                    'category' => $coursecat,
                    'courseid' => $courseid,
                    'assignmentid' => $assignmentid
                ];
                $comments = $DB->get_records_sql($sql, $params);
                return $comments;
    }
    public static function delete_comment($id) {
        global $DB;
        return $DB->delete_records('local_commentbank', ['id' => $id]);
    }
}