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
 * Bulk user registration script from a comma separated file
 *
 * @package    tool
 * @subpackage uploadcomments
 * @copyright  2020 Kieran Briggs (kbriggs@chartered.college)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('uploadcomments_form.php');
require_once('locallib.php');
require_once($CFG->libdir.'/csvlib.class.php');

global $DB;

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

require_login();
admin_externalpage_setup('tooluploadcomments');
require_capability('tool/uploadcomments:uploadcomments', context_system::instance());

$reqfields = array('id', 'comment', 'contextlevel', 'contextid');
$returnurl = new moodle_url('/admin/tool/uploadcomments/index.php');
$mform1 = new uploadcommentsform();

if (empty($iid)) {
    $mform1 = new uploadcommentsform();
    if ($formdata = $mform1->get_data()) {
        $iid = csv_import_reader::get_new_iid('uploadcomments');
        $cir = new csv_import_reader($iid, 'uploadcomments');
        $content = $mform1->get_file_content('commentsfile');
        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        $csvloaderror = $cir->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            print_error('csvloaderror', '', $returnurl, $csvloaderror);
        }
        // Test if columns ok.
        $filecolumns = uu_validate_comments_upload_columns($cir, $reqfields, $returnurl);

        // Continue to the next form.

    } else {
        echo $OUTPUT->header();

        echo $OUTPUT->heading_with_help(get_string('uploadcomments', 'tool_uploadcomments'), 'uploadcomments',
                'tool_uploadcomments');

        $mform1->display();
        echo $OUTPUT->footer();
        die;
    }
} else {
    $cir = new csv_import_reader($iid, 'uploadcomments');
    $filecolumns = uu_validate_comments_upload_columns($cir, $reqfields, $returnurl);
}

$mform2 = new uploadcommentspreviewform(null, array('columns' => $filecolumns,
                                        'data' => array('iid' => $iid, 'previewrows' => $previewrows)));


if ($formdata = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);

} else if ($formdata = $mform2->get_data()) {

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploaccommentsresults', 'tool_uploadcomments'));

    // Init csv import helper.
    $cir->init();
    $linenum = 1; // Column header is first line.
    $cpt = new uc_progress_tracker();
    $cpt->start();
    while ($line = $cir->next()) {
        $cpt->flush();
        $linenum++;
        $cpt->track('line', $linenum);
        $comment = new stdClass();
        foreach ($line as $keynum => $value) {
            $key = $filecolumns[$keynum];
            if (strpos($key, 'comment') === 0) {
                $comment->commenttext = clean_param($value, PARAM_NOTAGS);
                $cpt->track('comment', s($value), 'normal');
            }
            if (strpos($key, 'contextlevel') === 0) {
                $comment->contextlevel = $value;
                switch ($value) {
                    case '10':
                        $cpt->track('context', 'system', 'normal');
                        break;
                    case '40':
                        $cpt->track('context', 'category', 'normal');
                        break;
                    case '50':
                        $cpt->track('context', 'course', 'normal');
                        break;
                    case '70':
                        $cpt->track('context', 'assignment', 'normal');
                        break;
                }
            }
            if (strpos($key, 'contextid') === 0) {
                $comment->instanceid = $value;
                $cpt->track('instance', s($value), 'normal');
            }

        }
        $cpt->track('status', 'Added');

        $comment->timemodified = time();
        $comment->timecreated = time();

        $commentid = create_new_comment($comment);
    }
    $cpt->close();
    $uploaded = $linenum - 1;
    echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
    echo '<p>';
    echo get_string('commentsadded', 'tool_uploadcomments').': '.$uploaded.'<br />';



    echo $OUTPUT->box_end();


        echo $OUTPUT->continue_button($returnurl);


    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadcommentsspreview', 'tool_uploadcomments'));

// Preview table data.
$data = array();
$cir->init();
$linenum = 1; // Column header is first line.
$noerror = true; // Keep status of any error.
while ($linenum <= $previewrows && $fields = $cir->next()) {
    $linenum++;
    $rowcols = array();
    $rowcols['line'] = $linenum;
    foreach ($fields as $key => $field) {
        $rowcols[$filecolumns[$key]] = s(trim($field));
    }
    $rowcols['status'] = array();

    if (empty($rowcols['comment'])) {
        $rowcols['status'][] = get_string('missingcomment', 'tool_uploadcomments');
    }

    if (isset($rowcols['contextlevel'])) {

        switch ($rowcols['contextlevel']) {
            case '40':
                $rowcols['contextlevel'] = "Category";
                $rowcols['contextid'] = $DB->get_field('course_categories', 'name', array('id' => $rowcols['contextid']));
                if(!empty($rowcols['contextid'])){
                    break;
                }
                $rowcols['status'][] = get_string('incorrectcategoryid', 'tool_uploadcomments');
                break;

            case '50':
                $rowcols['contextlevel'] = "Course";
                $rowcols['contextid'] = $DB->get_field('course', 'shortname', array('id' => $rowcols['contextid']));
                if(!empty($rowcols['contextid'])){
                    break;
                }
                $rowcols['status'][] = get_string('incorrectcourseid', 'tool_uploadcomments');
                break;
            case '70':
                $rowcols['contextlevel'] = "Assignment";
                $rowcols['contextid'] = $DB->get_field('assign', 'name', array('id' => $rowcols['contextid']));
                if(!empty($rowcols['contextid'])){
                    break;
                }
                $rowcols['status'][] = get_string('incorrectassignmentid', 'tool_uploadcomments');
                break;
            case '10':
                $rowcols['contextlevel'] = "System";
                $rowcols['contextid'] = "N/A";
                break;
            default:
                $rowcols['contextdisplay'] = " ";
                $rowcols['status'][] = get_string('incorrectcontext', 'tool_uploadcomments');
        }
    }

     $rowcols['status'] = implode('<br />', $rowcols['status']);
    $data[] = $rowcols;
}
if ($fields = $cir->next()) {
    $data[] = array_fill(0, count($fields) + 2, '...');
}
$cir->close();

$table = new html_table();
$table->id = "ucpreview";
$table->attributes['class'] = 'generaltable';
$table->tablealign = 'center';
$table->summary = get_string('uploadcommentsspreview', 'tool_uploadcomments');
$table->head = array();
$table->data = $data;

$table->head[] = get_string('uccsvline', 'tool_uploadcomments');
foreach ($filecolumns as $column) {
    $table->head[] = $column;
}
$table->head[] = get_string('status');

echo html_writer::tag('div', html_writer::table($table), array('class' => 'flexible-wrap'));

$mform2->display();

echo $OUTPUT->footer();
die;