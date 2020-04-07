<?php

namespace local_commentbank;

defined('MOODLE_INTERNAL') || die();

use local_commentbank\services\commentbank;
use local_tlcore\datatable\query;

class actions {

    public static function get_comments() {
        $query = required_param('query', PARAM_TEXT);
        $cbservice = new commentbank();
        return $cbservice->run_query(new query($query));
    }
  /**
   * Get all assignments on a course
   * given the courseid
   *
   * @param int $courseid
   * @return \stdClass
   */
    public static function get_course_assignments($courseid = null) {
      global $DB;
      if (!$courseid) {
        $courseid = required_param('courseid', PARAM_INT);
      }
      $sql = "
      select cm.id, a.name from {course_modules} cm
      JOIN {modules} m ON cm.module = m.id
      JOIN {assign} a ON a.id = cm.instance
      AND  m.name = 'assign' AND a.course=:courseid";
      $result = $DB->get_records_sql($sql, ['courseid'=>$courseid]);
      return $result;

  }
}