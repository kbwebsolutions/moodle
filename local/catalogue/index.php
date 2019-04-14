<?php
// Standard GPL and phpdocs
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__. '/lib.php');
 
 
// Set up the page.
require_login();
$PAGE->set_context(context_system::instance());
$title = ('Course Catalogue');
$pagetitle = $title;
$url = new moodle_url("/local/catalogue/index.php");
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$courses = get_all_relevent_courses();

$output = $PAGE->get_renderer('local_catalogue');

echo $output->header();

foreach ($courses as $c) {
    //$courseimage = get_course_image($c);
    echo $c->id;
    $context = \context_course::instance($c->id);
    print_r($context);
    echo "<div class='cat-course'><a href='".$CFG->wwwroot."/course/view.php?id=".$c->id."'>";
    //echo local::course_coverimage_url($c->id)."<br />";
    echo $c->fullname.'<br />';
    echo "</a></div>";
}

$renderable = new \local_catalogue\output\frontpage($courses);
echo $output->render($renderable);
 
echo $output->footer();