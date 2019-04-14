<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Gets list of all courses in chosen categories to dislay
 * @return array
 */
function get_all_relevent_courses() {
    global $DB, $USER; 

    $idstring = get_config(null, 'catalogue_category_list');
    $ids = explode(",", $idstring);

    if(!empty($ids)) {
        list($coursesql, $params) = $DB->get_in_or_equal($ids);
        $sql = "SELECT * FROM {course} WHERE category $coursesql";
        $courseList = $DB->get_records_sql($sql, $params);
    } else {
        $courseList = '';
    }
    //return $ids;
    return $courseList;
}

/**
 * Prints out list of categories for the setting page
 */
function get_category_list() {
    global $DB;

    $sql = "SELECT id, name
            FROM {course_categories}
            WHERE visible = '1'";
    
    $categories = $DB->get_records_sql($sql);

    return $categories;
}

function get_course_image($course) {
    global $CFG; 

    foreach ($course->get_course_overviewfiles() as $file) {
        $isImage = $file->is_valid_image();
        if($isImage) {
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php",'/'.$file->get_contextid().'/',$file->get_filepath().$file->get_filename(),!$isImage);
        }
    }
    return $url;
}