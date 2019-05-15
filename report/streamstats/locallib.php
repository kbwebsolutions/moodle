<?php

function get_workstream_categories() {
    global $DB;

    $sql = 'SELECT cc.name, cc.id from {course_categories} as cc WHERE cc.visible = 1';
    $rs = $DB->get_records_sql($sql);

    return $rs;
}

function get_workstream_options() {


    $categories = get_workstream_categories();
    foreach($categories as $cat) {
        $options[$cat->id] = $cat->name;
    }

    $mform = $this->_mform;

    $mform->add_element('select', 'categories', get_string('catoptions', 'report_streamstats'), $options);



    return $mform;

}

function forum_statistics($category = 1) {
    global $DB;

    $sql = ""
}