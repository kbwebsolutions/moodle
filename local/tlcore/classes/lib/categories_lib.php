<?php

namespace local_tlcore\lib;

class categories_lib extends base_lib {
  /**
   * Gets an array of all category IDs => category names.
   * @return array [id:int => name:string]
   */
  public function names() {
    global $DB;

    $records = array_map(function($a) {
      return $a->name;
    }, $DB->get_records('course_categories', [], 'name ASC', 'id, name'));

    return $records;
  }

  /**
   * Gets an array of all course names that are in a given category.
   * @param  int $category_id Category ID
   * @return array [id:int => name:string]
   */
  public function get_course_names($category_id) {
    global $DB;

    $records = array_map(function($a) {
      return $a->name;
    }, $DB->get_records('course', ['category' => $category_id], 'name ASC', 'id, name'));
  
    return $records;
  }

  /**
   * Gets an array of course IDs that are in a given category.
   * @param  int $category_id Category ID
   * @return array [id]
   */
  public function get_courses($category_id) {
    global $DB;

    $records = array_map(function($a) {
      return (int)$a;
    }, $DB->get_fieldset_select('course', 'id', 'category = ?', [$category_id]));

    return $records;
  }
}