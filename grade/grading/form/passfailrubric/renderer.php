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
 * Contains renderer used for displaying passfailrubric
 *
 * @package    gradingform_passfailrubric
 * @copyright  2016 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author     Edwin Phillips <edwin.phillips@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Based on code originating from package gradingform_rubric
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $PAGE;
$PAGE->requires->js_call_amd('gradingform_passfailrubric/add_comments', 'init');
use local_commentbank\lib\comment_lib;

/**
 * Grading method plugin renderer
 *
 * @package    gradingform_passfailrubric
 * @copyright  2016 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Based on code originating from package gradingform_rubric
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_passfailrubric_renderer extends plugin_renderer_base {
    public $itemid = 0;
    /**
     * This function returns html code for displaying criterion. Depending on $mode it may be the
     * code to edit passfailrubric, to preview the passfailrubric, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_passfailrubric() to display the whole passfailrubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty criteria to the
     * passfailrubric being designed.
     * In this case it will use macros like {NAME}, {LEVELS}, {CRITERION-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode passfailrubric display mode, see {@link gradingform_passfailrubric_controller}
     * @param array $options display options for this passfailrubric, defaults are:
     *      {@link gradingform_passfailrubric_controller::get_default_options()}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array|null $criterion criterion data
     * @param string $levelsstr evaluated templates for this criterion levels
     * @param array|null $value (only in view mode) teacher's feedback on this criterion
     * @return string
     */
    public function criterion_template($mode, $options, $elementname = '{NAME}',
            $criterion = null, $levelsstr = '{LEVELS}', $value = null) {
            if ($criterion === null || !is_array($criterion) || !array_key_exists('id', $criterion)) {
                $criterion = array('id' => '{CRITERION-id}', 'description' => '{CRITERION-description}',
                    'sortorder' => '{CRITERION-sortorder}', 'class' => '{CRITERION-class}');
            } else {
                foreach (array('sortorder', 'description', 'class') as $key) {
                    // Set missing array elements to empty strings to avoid warnings.
                    if (!array_key_exists($key, $criterion)) {
                        $criterion[$key] = '';
                    }
                }
            }
            $descriptionclass = 'description';
            $criteriontemplate = html_writer::start_tag('tr', array('class' => 'criterion'. $criterion['class'],
                'id' => '{NAME}-criteria-{CRITERION-id}'));
            if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FULL) {
                $criteriontemplate .= html_writer::start_tag('td', array('class' => 'controls'));
                foreach (array('moveup', 'delete', 'movedown', 'duplicate') as $key) {
                    $value = get_string('criterion'.$key, 'gradingform_passfailrubric');
                    $button = html_writer::empty_tag('input',
                            array('type' => 'submit', 'name' => '{NAME}[criteria][{CRITERION-id}]['.$key.']',
                        'id' => '{NAME}-criteria-{CRITERION-id}-'.$key, 'value' => $value, 'title' => $value, 'tabindex' => -1));
                            $criteriontemplate .= html_writer::tag('div', $button, array('class' => $key));
                }
                $criteriontemplate .= html_writer::end_tag('td'); // Class controls.
                $criteriontemplate .= html_writer::empty_tag('input',
                        array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][sortorder]',
                            'value' => $criterion['sortorder']));
                        $description = html_writer::tag('textarea', s($criterion['description']),
                        ['class'=>'descriptionedit','name' => '{NAME}[criteria][{CRITERION-id}][description]', 'cols' => '70', 'rows' => '5']);
            } else {
                if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FROZEN) {
                    $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden',
                        'name' => '{NAME}[criteria][{CRITERION-id}][sortorder]', 'value' => $criterion['sortorder']));
                    $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden',
                        'name' => '{NAME}[criteria][{CRITERION-id}][description]', 'value' => $criterion['description']));
                }
                $description = s($criterion['description']);
            }
            if (isset($criterion['error_description'])) {
                $descriptionclass .= ' error';
            }
            $criteriontemplate .= html_writer::tag('td', $description,
                    array('class' => $descriptionclass. ' criteria-description ', 'id' => '{NAME}-criteria-{CRITERION-id}-description'));
            $levelsstrtable = html_writer::tag('table', html_writer::tag('tr',
                    $levelsstr, array('id' => '{NAME}-criteria-{CRITERION-id}-levels')));
            $levelsclass = 'levels';
            if (isset($criterion['error_levels'])) {
                $levelsclass .= ' error';
            }


            $currentremark = '';
            if (isset($value['remark'])) {
                $currentremark = $value['remark'];
            }

            /* Show feedback and grade to students after marking */
            if ($mode == gradingform_passfailrubric_controller::DISPLAY_VIEW) {
                /** TD-14 */
                global $DB;
                $scale = explode(',', $DB->get_field('scale', 'scale', ['name' => 'refer_fail_pass']));
                //$grade = $scale[$value['levelid'] - 1];
                $grade = $this->get_grade_level($value['levelid'], true);

                $criteriontemplate .= html_writer::start_tag('tr');
                $criteriontemplate .= html_writer::tag('td', get_string("criteriagrade", "gradingform_passfailrubric").' '. $grade, ['class' => 'remarkview criteria-grade']);
                $criteriontemplate .= html_writer::end_tag('tr');
                $criteriontemplate .= html_writer::start_tag('tr');
                $criteriontemplate .= html_writer::tag('td', $currentremark, ['class' => 'remarkview gradeview criteria-remarks']);
                $criteriontemplate .= html_writer::end_tag('tr');
            }

            $criteriontemplate .= html_writer::tag('td', $levelsstrtable, array('class' => $levelsclass));
            if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FULL) {
            }
            if ($mode == gradingform_passfailrubric_controller::DISPLAY_EVAL) {

            $remarklabeltext = get_string('criterionremark', 'gradingform_passfailrubric');
            $remarkparams = [
                'name' => '{NAME}[criteria][{CRITERION-id}][remark]',
                'id' => '{NAME}-criteria-{CRITERION-id}-remark',
                'cols' => '10', 'rows' => '5',
                'aria-label' => $remarklabeltext,
                'class' => 'remark'
            ];

            global $PAGE;
            // Detect Non editing teacher role by checking if they can add a new assignment.
            // There seems to be no function like check_role('noneditingteacher).
            // Then set to disabled so they can only add 'canned' comments from the popup.
            if (!has_capability('mod/assign:addinstance', $PAGE->context)) {
              $remarkparams['readonly'] = true;
              $remarkparams['class'] = 'remark look-disabled';
            }

            $input = html_writer::tag('textarea', s($currentremark), $remarkparams);

            $attributes = [
                'class' => 'add_comment',
                'id'=> 'criteria-'.$criterion['id'].'-remark'
            ];
            $commentbutton = html_writer::tag('button', 'Comments', $attributes);
            $input .=$commentbutton;
            global $PAGE;
            if (has_capability('gradingform/passfailrubric:view_grade_history', $PAGE->context)) {
                $input .= html_writer::start_tag('div', ['class' => 'history']);
                    $input .= '<div id="historylabel">'.get_string('gradehistory', 'gradingform_passfailrubric').'</div>';
                    $input .= $this->get_grade_history($criterion['id']);
                    $input .= html_writer::end_tag('div');
                }
            $remarkattributes = ['class'=>'remarkinput'];
            $criteriontemplate .= html_writer::tag('td', $input, $remarkattributes);
    }
        /* replace template constants such as CRITERION-id with current real values */
        $criteriontemplate = str_replace('{NAME}', $elementname, $criteriontemplate);
        $criteriontemplate = str_replace('{CRITERION-id}', $criterion['id'], $criteriontemplate);
        return $criteriontemplate;
    }

    /**
     * Get the history of grade overrides for this item.
     * @todo add more divs with class names for later
     * beautification.
     *
     * @param integer $itemid
     * @return string grade history in a div
     */
    public function get_override_history(int $itemid) :string {
        global $DB;
        $sql = 'SELECT g.id,g.grade,g.explanation,u.firstname,u.lastname,g.timecreated FROM {gradingform_pfrbric_grades} g join {user} u on g.authoredby=u.id
         where itemid = :itemid order by g.id desc';
        $overridehistory = $DB->get_records_sql($sql, ['itemid' => $itemid]);
        $history = '';
        foreach ($overridehistory as $h) {
            $history .= '<div class="override_history">' . $h->firstname . ' ' . $h->lastname . ': ';
            $history .= $this->get_grade_level($h->grade) . ' (';
            $history .= userdate($h->timecreated, "%c").'): ';
            $history .=  $h->explanation.'</div>';
        }
        return $history;
    }
    /**
     * Pass in the number for the grade level
     * pull apart the scale and return the string
     * for that grade, e.g. pass in 1 return "Fail"
     *
     * @param integer $level
     * @return string
     */
  public function get_grade_level($level, bool $fordisplay = false) : ?string{
      global $DB;
      // Convert short string of grade to long string.
      $longlevel = ['Fail' =>'Not met', 'Refer'=>'Partially met', 'Pass'=>'Met'];
      if (array_key_exists($level, $longlevel)) {
        return $longlevel[$level];
      }
      $grade = "";
      $result = $DB->get_field('scale', 'scale', ['name' => 'refer_fail_pass']);
      if ($result) {
        $scale = explode(',', $result);
        $grade = $scale[$level - 1];
      }
      // Convert number to long grade.
      if ($fordisplay) {
        return $longlevel[$grade];
      }
      // Return short grade
      return $grade;

    }


    /**
     * Get history from fillings table
     *
     * @param integer $criterionid
     * @return string
     */
    public function get_grade_history(int $criterionid) : string {
      global $DB;
      $inactive = gradingform_instance::INSTANCE_STATUS_ACTIVE;
      $sql = "SELECT DISTINCT gi.timemodified, u.username,u.firstname,u.lastname, f.levelid AS levelmet
              FROM {grading_instances} gi
              JOIN {gradingform_pfrbric_fillings} f ON gi.id=instanceid
              JOIN {user} u ON gi.raterid=u.id
              WHERE f.criterionid=:criterionid
              AND gi.itemid=:itemid
              AND gi.status >= :inactive
              ORDER BY gi.timemodified DESC";
      $gradehistory = $DB->get_records_sql($sql, ['criterionid' => $criterionid, 'itemid' => $this->itemid, 'inactive' => $inactive]);
      $history = '';

      foreach ($gradehistory as $h) {
        $history .= '<div class="grade_history">' . $h->firstname . ' '. $h->lastname . ': ';
        $history .= $this->get_grade_level($h->levelmet, true) . '(';
        $history .= userdate($h->timemodified, get_string('strftimedatetimeshort')).')</div>';
      }
      return $history;
    }

    /**
     * This function returns html code for displaying one level of one criterion. Depending on $mode
     * it may be the code to edit passfailrubric,
     *      to preview the passfailrubric, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_passfailrubric() to display the whole passfailrubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty level to the
     * criterion during the design of passfailrubric.
     * In this case it will use macros like {NAME}, {CRITERION-id}, {LEVEL-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them.
     * script might stop working.
     *
     * @param int $mode passfailrubric display mode see
     *      {@link gradingform_passfailrubric_controller}
     * @param array $options display options for this passfailrubric,
     *      defaults are: {@link gradingform_passfailrubric_controller::get_default_options()}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string|int $criterionid either id of the nesting criterion or a macro for template
     * @param array|null $level level data, also in view mode it might also have
     *      property $level['checked'] whether this level is checked
     * @return string
     */
    public function level_template($mode, $options, $elementname = '{NAME}', $criterionid = '{CRITERION-id}', $level = null) {
        // TODO MDL-31235 definition format.
        $value = 1;
        switch(strtolower($level['definition'])){
                case "refer":
                $value = 0;
                break;
                case "fail":
                $value = 1;
                break;
                case "pass":
                $value = 2;
                break;
            }
        if (!isset($level['id'])) {
            $level = array('id' => '{LEVEL-id}', 'definition' => '{LEVEL-definition}',
                'class' => '{LEVEL-class}', 'checked' => false);
        } else {
            foreach (array('score', 'definition', 'class', 'checked') as $key) {
                // Set missing array elements to empty strings to avoid warnings.
                if (!array_key_exists($key, $level)) {
                    $level[$key] = '';
                }
            }
        }

        // Template for one level within one criterion.
        $tdattributes = array('id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}', 'class' => 'level'. $level['class']);
        if (isset($level['tdwidth'])) {
            $tdattributes['width'] = round($level['tdwidth']).'%';
        }

        $leveltemplate = html_writer::start_tag('td', $tdattributes);
        $leveltemplate = '';

        if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FULL) {
            $definition = html_writer::tag('textarea', s($level['definition']),[]);
        } else {
            if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FROZEN) {
                $leveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden',
                    'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][definition]', 'value' => $level['definition']));
            }
            $definition = s($level['definition']);
        }

        $definitionclass = 'definition';
        if (isset($level['error_definition'])) {
            $definitionclass .= ' error';
        }
        if ($mode == gradingform_passfailrubric_controller::DISPLAY_EVAL) {
            /* displayed during grading */
            $ischecked = $level['checked'] ? ['checked' => 'checked'] : [];

            $radio_buttons = [
                'type' => 'radio',
                'name' => '{NAME}[criteria][{CRITERION-id}][levelid]',
                'value' => $value
            ];
            $radio_buttons += $ischecked;

            $input = html_writer::empty_tag('input', array('type' => 'radio',
            'name' => '{NAME}[criteria][{CRITERION-id}][levelid]',
            'value' => $level['id']) + ($level['checked'] ? array('checked' => 'checked') : array()));
            $leveltemplate = html_writer::start_tag('td', $tdattributes);
            $leveltemplate .= html_writer::tag('div', $input, array('class' => 'radio'));
            $leveltemplate .= html_writer::tag('div', $definition, array('class' => $definitionclass,
            'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-definition'));

        }
        if ($mode == gradingform_passfailrubric_controller::DISPLAY_EVAL_FROZEN && $level['checked']) {
            $leveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => '{NAME}[criteria][{CRITERION-id}][levelid]', 'value' => $level['id']));
        }

        $leveltemplate .= html_writer::end_tag('div'); // Class .level-wrapper.
        $leveltemplate .= html_writer::end_tag('td'); // Class .level.

        $leveltemplate = str_replace('{NAME}', $elementname, $leveltemplate);
        $leveltemplate = str_replace('{CRITERION-id}', $criterionid, $leveltemplate);
        $leveltemplate = str_replace('{LEVEL-id}', $level['id'], $leveltemplate);
        return $leveltemplate;
    }

    /**
     * This function returns html code for displaying passfailrubric template (content before and after
     * criteria list). Depending on $mode it may be the code to edit passfailrubric, to preview the passfailrubric,
     * to evaluate somebody or to review the evaluation.
     *
     * This function is called from display_passfailrubric() to display the whole passfailrubric.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode passfailrubric display mode see {@link gradingform_passfailrubric_controller}
     * @param array $options display options for this passfailrubric,
     *      defaults are: {@link gradingform_passfailrubric_controller::get_default_options()}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string $criteriastr evaluated templates for this passfailrubric's criteria
     * @return string
     */
    protected function passfailrubric_template($mode, $options, $elementname, $criteriastr, $grademenu = null, $grade, $itemid= null) {
        $classsuffix = ''; // CSS suffix for class of the main div. Depends on the mode.
        switch ($mode) {
            case gradingform_passfailrubric_controller::DISPLAY_EDIT_FULL:
                $classsuffix = ' editor editable';
                break;
            case gradingform_passfailrubric_controller::DISPLAY_EDIT_FROZEN:
                $classsuffix = ' editor frozen';
                break;
            case gradingform_passfailrubric_controller::DISPLAY_PREVIEW:
            case gradingform_passfailrubric_controller::DISPLAY_PREVIEW_GRADED:
                $classsuffix = ' editor preview';
                break;
            case gradingform_passfailrubric_controller::DISPLAY_EVAL:
                $classsuffix = ' evaluate editable';
                break;
            case gradingform_passfailrubric_controller::DISPLAY_EVAL_FROZEN:
                $classsuffix = ' evaluate frozen';
                break;
            case gradingform_passfailrubric_controller::DISPLAY_REVIEW:
                $classsuffix = ' review';
                break;
            case gradingform_passfailrubric_controller::DISPLAY_VIEW:
                $classsuffix = ' view';
                break;
        }

        $passfailrubrictemplate = html_writer::start_tag('div', array('id' => 'passfailrubric-{NAME}',
            'class' => 'clearfix gradingform_passfailrubric'.$classsuffix));


        $passfailrubrictemplate .= html_writer::tag('table', $criteriastr,
                array('class' => 'criteria', 'id' => '{NAME}-criteria'));
        if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FULL) {
            $value = get_string('addcriterion', 'gradingform_passfailrubric');
            $input = html_writer::empty_tag('input', array('type' => 'submit',
                'name' => '{NAME}[criteria][addcriterion]', 'id' => '{NAME}-criteria-addcriterion',
                'value' => $value, 'title' => $value));
            $passfailrubrictemplate .= html_writer::tag('div', $input, array('class' => 'addcriterion'));
        }
        $passfailrubrictemplate .= $this->passfailrubric_edit_options($mode, $options);
        $attributes = [
            'id' => 'comment_popup',
            'style'=>'display:none;background-color:lightgrey'
        ];
        global $PAGE, $DB;
        $sql = "
        SELECT cm.id as cmid from {course_modules} cm
        JOIN {modules} m on cm.module=m.id
        JOIN {assign_grades} ag on ag.assignment=cm.instance
        WHERE m.name='assign' and ag.id=:itemid
        ";
        $comments = new \stdClass();
        $record = $DB->get_record_sql($sql, ['itemid' => $itemid]);
        if ($record) {
           $comments = comment_lib::get_module_comments($PAGE->course->id, $record->cmid);
        }
        $passfailrubrictemplate .= html_writer::start_tag('div', $attributes);
        foreach ($comments as $comment) {
            $passfailrubrictemplate .= '<div tabindex="0" aria-selected="false" role="button" class="reusable_remark">'.$comment->commenttext.'</div>';
        }
        $passfailrubrictemplate .= html_writer::end_tag('div');

        global $PAGE;
        // Grade menu for overriding the calculated grade.
            if ($grademenu) {
                $grademenu = ['select' => 'select'] + $grademenu;
                $params = [
                    'class' => 'grade-override',
                ];
                $passfailrubrictemplate .= html_writer::tag('label',
                get_string('overallgrade', 'gradingform_passfailrubric'), []);
                $passfailrubrictemplate .= html_writer::select(
                    $grademenu, 'advancedgrading[override-grade]', $grade, false, $params
                );
                $params = [
                    'name' => 'advancedgrading[explanation]',
                    'rows' => 4,
                    'id' => 'advancedgrading-explanation',
                    'aria-label' => 'Grade override explanation',
                    'class' => 'grade-override',
                    'placeholder' => get_string('overrideplaceholder', 'gradingform_passfailrubric')
                ];
              $input = html_writer::tag('textarea', '', $params);
              /* write a plain text log of the grade override history */
              $passfailrubrictemplate .= $input;
              if (has_capability('gradingform/passfailrubric:view_grade_overrides', $PAGE->context)) {
                 $passfailrubrictemplate .= $this->get_moderation_details($itemid);
                 $overridehistory = $this->get_override_history($itemid);
              }
              $passfailrubrictemplate .= $overridehistory;
            }


        return str_replace('{NAME}', $elementname, $passfailrubrictemplate);
    }
    /**
     * Get information on who has clicked the moderated checkbox
     * on the grading form
     *
     * @param int $itemid
     * @return void
     */
    protected function get_moderation_details(int $itemid) :string {
        global $DB;

        $html = '';

        $moderation = $DB->get_record('gradingform_pfrbric_moderate', ['item' => $itemid]);

        $html .= html_writer::start_tag('div', ['class' => 'moderation']);

        $checkbox = [
          'type' => 'checkbox',
          'name' => 'advancedgrading[ismoderated]',
          'id' => 'advancedgrading-ismoderated',
        ];
        if ($moderation) {
          $checkbox['checked'] = 'checked';
          $moderator = $DB->get_record('user', ['id' => $moderation->userid]);
          $label = get_string('moderatedby', 'gradingform_passfailrubric') . ": " . fullname($moderator) . ' on ' . date("D d-M-Y", $moderation->timecreated);
        } else {
          $label = get_string('confirmmoderation', 'gradingform_passfailrubric');
        }
        $html .= html_writer::empty_tag('input', $checkbox);
        $html .= html_writer::label($label, 'ismoderated', '', ['id' => 'moderated_by', 'class'=>'ismoderated-label']);
        $html .= html_writer::end_tag('div');
        return $html;
    }
    /**
     * Generates html template to view/edit the passfailrubric options. Expression {NAME} is used in
     * template for the form element name
     *
     * @param int $mode passfailrubric display mode see {@link gradingform_passfailrubric_controller}
     * @param array $options display options for this passfailrubric,
     *      defaults are: {@link gradingform_passfailrubric_controller::get_default_options()}
     * @return string
     */
    protected function passfailrubric_edit_options($mode, $options) {
        if ($mode != gradingform_passfailrubric_controller::DISPLAY_EDIT_FULL
                && $mode != gradingform_passfailrubric_controller::DISPLAY_EDIT_FROZEN
                && $mode != gradingform_passfailrubric_controller::DISPLAY_PREVIEW) {
            // Options are displayed only for people who can manage.
            return;
        }
        $html = '';
        $html .= html_writer::tag('div', get_string('passfailrubricoptions', 'gradingform_passfailrubric'),
                array('class' => 'optionsheading'));
        $attrs = array('type' => 'hidden', 'name' => '{NAME}[options][optionsset]', 'value' => 1);
        foreach ($options as $option => $value) {
            $html .= html_writer::start_tag('div', array('class' => 'option '.$option));
            $attrs = array('name' => '{NAME}[options]['.$option.']', 'id' => '{NAME}-options-'.$option);
            switch ($option) {
               // case 'showdescriptionteacher':
                case 'criterionordering':
                case 'autopopulatecomments':
                /*
                    // Display option as dropdown.
                    $html .= html_writer::label(get_string($option, 'gradingform_passfailrubric'),
                            $attrs['id'], false, array('class' => 'label'));
                    $value = (int)(!!$value); // Make sure $value is either 0 or 1.
                    if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FULL) {
                        $selectoptions = array(
                            0 => get_string($option.'0', 'gradingform_passfailrubric'),
                            1 => get_string($option.'1', 'gradingform_passfailrubric')
                        );
                        $valuestr = html_writer::select($selectoptions, $attrs['name'], $value, false, array('id' => $attrs['id']));
                        $html .= html_writer::tag('span', $valuestr, array('class' => 'value'));
                    } else {
                        $html .= html_writer::tag('span', get_string($option.$value, 'gradingform_passfailrubric'),
                                array('class' => 'value'));
                        if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FROZEN) {
                            $html .= html_writer::empty_tag('input', $attrs + array('type' => 'hidden', 'value' => $value));
                        }
                    }*/
                    break;
                default:
                    if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FROZEN && $value) {
                        $html .= html_writer::empty_tag('input', $attrs + array('type' => 'hidden', 'value' => $value));
                    }
                    // Display option as checkbox.
                    $attrs['type'] = 'checkbox';
                    $attrs['value'] = 1;
                    if ($value) {
                        $attrs['checked'] = 'checked';
                    }
                    if ($mode == gradingform_passfailrubric_controller::DISPLAY_EDIT_FROZEN ||
                            $mode == gradingform_passfailrubric_controller::DISPLAY_PREVIEW) {
                        $attrs['disabled'] = 'disabled';
                        unset($attrs['name']);
                    }
                    $html .= html_writer::empty_tag('input', $attrs);
                    $html .= html_writer::tag('label', get_string($option, 'gradingform_passfailrubric'),
                            array('for' => $attrs['id']));
                    break;
            }
            $html .= html_writer::end_tag('div'); // Class option.
        }
        $html .= html_writer::end_tag('div'); // Class options.

        return $html;
    }

    /**
     * This function returns html code for displaying passfailrubric. Depending on $mode it may be the code
     * to edit passfailrubric, to preview the passfailrubric, to evaluate somebody or to review the evaluation.
     *
     * It is very unlikely that this function needs to be overriden by theme. It does not produce
     * any html code, it just prepares data about passfailrubric design and evaluation, adds the CSS
     * class to elements and calls the functions level_template, criterion_template and
     * passfailrubric_template
     *
     * @param array $criteria data about the passfailrubric design
     * @param array $options display options for this passfailrubric,
     *      defaults are: {@link gradingform_passfailrubric_controller::get_default_options()}
     * @param int $mode passfailrubric display mode, see {@link gradingform_passfailrubric_controller}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array $values evaluation result
     * @return string
     */
    public function display_passfailrubric($criteria, $options, $mode,
            $elementname = null, $values = null, $grademenu = null, $grade = null) {
        $criteriastr = '';
        $cnt = 0;
        if(isset($values['itemid'])){
            $this->itemid = $values['itemid'];
        }
        foreach ($criteria as $id => $criterion) {
            $criterion['class'] = $this->get_css_class_suffix($cnt++, count($criteria) - 1);
            $criterion['id'] = $id;
            $levelsstr = '';
            $levelcnt = 0;
            if (isset($values['criteria'][$id])) {
                $criterionvalue = $values['criteria'][$id];
            } else {
                $criterionvalue = null;
            }
            foreach ($criterion['levels'] as $levelid => $level) {
                $level['id'] = $levelid;
                $level['class'] = $this->get_css_class_suffix($levelcnt++, count($criterion['levels']) - 1);
                $level['checked'] = (isset($criterionvalue['levelid']) && ((int)$criterionvalue['levelid'] === $levelid));
                if ($level['checked'] &&
                        ($mode == gradingform_passfailrubric_controller::DISPLAY_EVAL_FROZEN
                        || $mode == gradingform_passfailrubric_controller::DISPLAY_REVIEW
                        || $mode == gradingform_passfailrubric_controller::DISPLAY_VIEW)
                    ) {
                    $level['class'] .= ' checked';
                    // In mode DISPLAY_EVAL the class 'checked' will be added by JS if it is enabled.
                    // If JS is not enabled, the 'checked' class will only confuse.
                }
                if (isset($criterionvalue['savedlevelid']) && ((int)$criterionvalue['savedlevelid'] === $levelid)) {
                    $level['class'] .= ' currentchecked';
                }
                $level['tdwidth'] = 100 / count($criterion['levels']);
                $levelsstr .= $this->level_template($mode, $options, $elementname, $id, $level);
            }
            $criteriastr .= $this->criterion_template($mode, $options, $elementname, $criterion, $levelsstr, $criterionvalue);
        }

        $itemid = null;
        if (isset($values['itemid'])) {
            $itemid = $values['itemid'];
        }

        return $this->passfailrubric_template($mode, $options, $elementname, $criteriastr, $grademenu, $grade, $itemid);
    }

    /**
     * Help function to return CSS class names for element (first/last/even/odd) with leading space
     *
     * @param int $idx index of this element in the row/column
     * @param int $maxidx maximum index of the element in the row/column
     * @return string
     */
    protected function get_css_class_suffix($idx, $maxidx) {
        $class = '';
        if ($idx == 0) {
            $class .= ' first';
        }
        if ($idx == $maxidx) {
            $class .= ' last';
        }
        if ($idx % 2) {
            $class .= ' odd';
        } else {
            $class .= ' even';
        }
        return $class;
    }

    /**
     * Displays for the student the list of instances or default content if no instances found
     *
     * @param array $instances array of objects of type gradingform_passfailrubric_instance
     * @param string $defaultcontent default string that would be displayed without advanced grading
     * @param boolean $cangrade whether current user has capability to grade in this context
     * @return string
     */
    public function display_instances($instances, $defaultcontent, $cangrade) {
        $return = '';
        if (count($instances)) {
            $idx = 0;
            foreach ($instances as $instance) {
                $return .= $this->display_instance($instance, $idx++, $cangrade);
            }
        }
        /* @todo replace with html_writer output mavg */
        return "<div class='passfail-grade'>Your overall grade for this assessment is: ".$defaultcontent. "</div><tr><td></td><td>" . $return . "</td></tr>";
    }

    public function get_grade_longform( ? string $level) : string{
        $longform = ['Fail' => 'Not met', 'Refer' => 'Partially met', 'Pass' => 'Met'];
        if (array_key_exists($level, $longform)) {
            return $longform[$level];
        }
        return "";
    }
    /**
     * Displays one grading instance
     *
     * @param gradingform_passfailrubric_instance $instance
     * @param int $idx unique number of instance on page
     * @param bool $cangrade whether current user has capability to grade in this context
     */
    public function display_instance(gradingform_passfailrubric_instance $instance, $idx, $cangrade) {
        $criteria = $instance->get_controller()->get_definition()->passfailrubric_criteria;
        $options = $instance->get_controller()->get_options();
        $values = $instance->get_passfailrubric_filling();
        if ($cangrade) {
            $mode = gradingform_passfailrubric_controller::DISPLAY_REVIEW;
            $showdescription = $options['showdescriptionteacher'];
        } else {
            $mode = gradingform_passfailrubric_controller::DISPLAY_VIEW;
            $showdescription = null;
        }
        $output = '';
        if ($showdescription) {
            $output .= $this->box($instance->get_controller()->get_formatted_description(),
                    'gradingform_passfailrubric-description');
        }
        $output .= $this->display_passfailrubric($criteria, $options, $mode, 'passfailrubric'.$idx, $values);
        return $output;
    }

    /**
     * Displays confirmation that students require re-grading
     *
     * @param string $elementname
     * @param int $changelevel
     * @param string $value
     * @return string
     */
    public function display_regrade_confirmation($elementname, $changelevel, $value) {
        $html = html_writer::start_tag('div', array('class' => 'gradingform_passfailrubric-regrade'));
        if ($changelevel <= 2) {
            $html .= html_writer::label(get_string('regrademessage1', 'gradingform_passfailrubric'),
                    'menu' . $elementname . 'regrade');
            $selectoptions = array(
                0 => get_string('regradeoption0', 'gradingform_passfailrubric'),
                1 => get_string('regradeoption1', 'gradingform_passfailrubric')
            );
            $html .= html_writer::select($selectoptions, $elementname.'[regrade]', $value, false);
        } else {
            $html .= get_string('regrademessage5', 'gradingform_passfailrubric');
            $html .= html_writer::empty_tag('input', array('name' => $elementname.'[regrade]', 'value' => 1, 'type' => 'hidden'));
        }
        $html .= html_writer::end_tag('div');
        return $html;
    }

}
