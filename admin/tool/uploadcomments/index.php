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
 * @subpackage uploaduser
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('uploadcomments_form.php');
require_once ('locallib.php');
require_once($CFG->libdir.'/csvlib.class.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

require_login();
admin_externalpage_setup('tooluploadcomments');
require_capability('tool/uploadcomments:uploadcomments', context_system::instance());

$req_fields = array('id', 'comment','contextlevel', 'contextid');
$returnurl = new moodle_url('/admin/tool/uploadcomments/index.php');
$mform1 = new uploadcommentsform();

if (empty($iid)) {
    $mform1 = new uploadcommentsform();
    if ($formdata = $mform1->get_data()) {
        $iid = csv_import_reader::get_new_iid('uploadcomments');
        $cir = new csv_import_reader($iid, 'uploadcomments');
print_object($iid);
        $content = $mform1->get_file_content('commentsfile');
        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        $csvloaderror = $cir->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            print_error('csvloaderror', '', $returnurl, $csvloaderror);
        }
        // test if columns ok
        $filecolumns = uu_validate_comments_upload_columns($cir, $req_fields, $returnurl);

        // continue to form2

    } else {
        echo $OUTPUT->header();

        echo $OUTPUT->heading_with_help(get_string('uploadcomments', 'tool_uploadcomments'), 'uploadcomments',
                'tool_uploadcomments');

        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
} else {

}

$mform2 = new uploadcommentspreviewform(null, array('columns'=>$filecolumns, 'data'=>array('iid'=>$iid, 'previewrows'=>$previewrows)));

// preview table data
$data = array();
$cir->init();
$linenum = 1; //column header is first line
$noerror = true; // Keep status of any error.
while ($linenum <= $previewrows and $fields = $cir->next()) {
    $linenum++;
    $rowcols = array();
    $rowcols['line'] = $linenum;
    foreach($fields as $key => $field) {
        $rowcols[$filecolumns[$key]] = s(trim($field));
    }
    $rowcols['status'] = array();

    if (empty($rowcols['comment'])) {
       $rowcols['status'][] = get_string('missingcomment');
    }

    if (isset($rowcols['contextlevel'])) {
        $rowcols['contextlevel'] = $rowcols['contextlevel'];
    }

    if (isset($rowcols['contextid'])) {
        $rowcols['contextid'] = $rowcols['contextid'];
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


    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('uploadcommentsspreview', 'tool_uploadcomments'));

    echo html_writer::tag('div', html_writer::table($table), array('class'=>'flexible-wrap'));
$mform2->display();
    echo $OUTPUT->footer();
