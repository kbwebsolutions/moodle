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
 * stats report
 *
 * @package    report
 * @subpackage Workstream stats
 * @copyright  1999 Kieran Briggs <kbriggs@chartered.college>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/report/streamstats/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

$context = context_user::instance($USER->id);


$PAGE->set_url('/report/streamstats/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'report_streamstats'));
$PAGE->set_heading(get_string('header', 'report_streamstats'));
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('mystats', 'report_streamstats'));;

$content = get_workstream_categories();

foreach ($content as $c) {
    echo '<p>'.$c->name.'</p>';
}

$stats = forum_statistics($category);


echo $OUTPUT->footer();