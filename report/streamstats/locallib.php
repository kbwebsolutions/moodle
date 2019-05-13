<?php

function get_workstream_categories() {
    global $DB;

    $sql = 'SELECT cc.name, cc.idnumber from {course_categories} WHERE cc.visible = 1';
    $rs = $DB->get_record_sql($sql);

    return $rs;
}