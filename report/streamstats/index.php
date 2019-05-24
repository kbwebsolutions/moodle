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
 * @package    report
 * @subpackage streamstats
 * @copyright  1999 Kieran Briggs <kbriggs@chartered.college>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/report/streamstats/locallib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/report/streamstats/classes/queries.php');
require_once($CFG->dirroot.'/report/streamstats/classes/streamstats_selection_form.php');

require_login();

$context = context_user::instance($USER->id);


$PAGE->set_url('/report/streamstats/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'report_streamstats'));
$PAGE->set_heading(get_string('header', 'report_streamstats'));
$PAGE->set_pagelayout('report');
//$output = $PAGE->get_renderer('report_streamstats');

echo $OUTPUT->header();

//echo $OUTPUT->heading(get_string('mystats', 'report_streamstats'));

$content = get_workstream_categories();



$components = array('0' => get_string('all', 'report_streamstats'));
foreach($content as $cat) {
    $components[$cat->id] = $cat->name;
}

// Create the filter form for the table.
$filterselection = new report_streamstats_selection_form(null, array('components' => $components));


$filterselection->display();


$queries = new queries();
$queries->category = 1; //TODO populate this from the form
$queries->timeback = 14; //TODO populate this from the form
$queries->studentrole = 5; // TODO populate from a popup somewhere

$formetter = new NumberFormatter('en_gb', NumberFormatter::PERCENT);
$forumstatistics = $queries->get_forum_discussions();
$participants = $queries->get_participant_numbers();

// Module Data
echo '<h3>Course User Details</h3>';
echo '<table data-region="user-stats" class="generaltable">';
echo '<tbody><thead><tr><th class="header">Module</th><th class="header">Particpants</th><th class="header">Active Users*</th><th class="header">Completed Activites</th><th class="header">Module Completion Rate</th></tr></thead>';
foreach($participants as $p) {
    if ($p->participants <> 0 && $p->completions <> 0) {
        $comprate = $formetter->format( $p->completions / $p->participants);
    } else {
        $comprate = 0;
    }
    if ($p->participants <> 0 && $p->activeusers <> 0) {
        $activeusers = $formetter->format( $p->activeusers / $p->participants);
    } else {
        $activeusers = 0;
    }
    echo '<tr><td>'.$p->fullname.'</td><td>'.$p->participants.'</td><td>'.$activeusers.'</td><td>'.$p->completedactivities,'</td><td>'.$comprate.'</td></tr>';
}
echo '</tbody></table>';
echo '<p>*Active users are users who have visited the module in the last 14 days</p>';

// Discussion Data
echo '<h3>Discussion Statistics for this Programme</h3>';
echo '<table data-region="forum-stats" class="generaltable">';
echo '<tbody><thead><tr><th class="header">Module</th><th class="header">Forum</th><th class="header">Total Discussions</th><th class="header">Total Posts</th><th class="header">Student Engagement</th></tr></thead>';

foreach ($forumstatistics as $fs) {
    $studentsaccessed = (int)$fs->totalstudentsinteracted;
    $totalstudents = (int)$fs->totalstudentscourse;
    if ($studentsaccessed <> 0 && $totalstudents <> 0) {
        $studentengagement = $formetter->format($studentsaccessed / $totalstudents);
    } else {
        $studentengagement = '0%';
    }
    echo '<tr><td>'.$fs->module.'</td><td>'.$fs->forum.'</td><td>'.$fs->discussions.'</td><td>'.$fs->totalposts.'</td><td>'.$studentengagement.'</td></tr>';

}

echo '</tr></tbody></table>';

//$disussions = new \local_streamstats\output\forumstats();
//echo $ouput->render($disussions);


echo $OUTPUT->footer();