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
 * Grading method controller for the Simple Feedback Rubric plugin
 *
 * @package    gradingform_passfailrubric
 * @copyright  2016 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author     Edwin Phillips <edwin.phillips@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Based on code originating from package gradingform_rubric
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/grading/form/lib.php');

/**
 * This controller encapsulates the passfailrubric grading logic
 *
 * @package    gradingform_passfailrubric
 * @copyright  2016 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Based on code originating from package gradingform_rubric
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_passfailrubric_controller extends gradingform_controller {
    // Modes of displaying the passfailrubric (used in gradingform_passfailrubric_renderer).
    /** Simple Feedback Rubric display mode: For editing (moderator or teacher creates a passfailrubric) */
    const DISPLAY_EDIT_FULL = 1;
    /** Simple Feedback Rubric display mode: Preview the passfailrubric design with hidden fields */
    const DISPLAY_EDIT_FROZEN = 2;
    /** Simple Feedback Rubric display mode: Preview the passfailrubric design (for person with manage permission) */
    const DISPLAY_PREVIEW = 3;
    /** Simple Feedback Rubric display mode: Preview the passfailrubric (for people being graded) */
    const DISPLAY_PREVIEW_GRADED = 8;
    /** Simple Feedback Rubric display mode: For evaluation, enabled (teacher grades a student) */
    const DISPLAY_EVAL = 4;
    /** Simple Feedback Rubric display mode: For evaluation, with hidden fields */
    const DISPLAY_EVAL_FROZEN = 5;
    /** Simple Feedback Rubric display mode: Teacher reviews filled passfailrubric */
    const DISPLAY_REVIEW = 6;
    /** Simple Feedback Rubric display mode: Dispaly filled passfailrubric (i.e. students see their grades) */
    const DISPLAY_VIEW = 7;
    /** These match the values in the refer_fail_pass scale which is independent of this plugin */
    const PASS_GRADE  = 3;
    const FAIL_GRADE  = 2;
    const REFER_GRADE = 1;


    /**
     * Extends the module settings navigation with the passfailrubric grading settings
     *
     * This function is called when the context for the page is an activity module with the
     * FEATURE_ADVANCED_GRADING, the user has the permission moodle/grade:managegradingforms
     * and there is an area with the active grading method set to 'passfailrubric'.
     *
     * @param settings_navigation $settingsnav {@link settings_navigation}
     * @param navigation_node $node {@link navigation_node}
     */
    public function extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node=null) {
        $node->add(get_string('definepassfailrubric', 'gradingform_passfailrubric'),
            $this->get_editor_url(), settings_navigation::TYPE_CUSTOM,
            null, null, new pix_icon('icon', '', 'gradingform_passfailrubric'));
    }

    /**
     * Extends the module navigation
     *
     * This function is called when the context for the page is an activity module with the
     * FEATURE_ADVANCED_GRADING and there is an area with the active grading method set to the given plugin.
     *
     * @param global_navigation $navigation {@link global_navigation}
     * @param navigation_node $node {@link navigation_node}
     */
    public function extend_navigation(global_navigation $navigation, navigation_node $node=null) {
        if (has_capability('moodle/grade:managegradingforms', $this->get_context())) {
            // No need for preview if user can manage forms, he will have link to manage.php in settings instead.
            return;
        }
    }

    /**
     * Saves the passfailrubric definition into the database
     *
     * @see parent::update_definition()
     * @param stdClass $newdefinition passfailrubric definition data
     * as coming from gradingform_passfailrubric_editpassfailrubric::get_data()
     * @param int|null $usermodified optional userid of the author of the definition, defaults to the current user
     */
    public function update_definition(stdClass $newdefinition, $usermodified = null) {
        $this->update_or_check_passfailrubric($newdefinition, $usermodified, true);
        if (isset($newdefinition->passfailrubric['regrade']) && $newdefinition->passfailrubric['regrade']) {
            $this->mark_for_regrade();
        }
    }

    /**
     * Either saves the passfailrubric definition into the database or check if it has been changed.
     * Returns the level of changes:
     * 0 - no changes
     * 1 - only texts or criteria sortorders are changed, students probably do not require re-grading
     * 2 - added levels, students still may not require re-grading
     * 3 - removed criteria or added levels or changed number of points,
     *      students require re-grading but may be re-graded automatically
     * 4 - removed levels - students require re-grading and not all students may be re-graded automatically
     * 5 - added criteria - all students require manual re-grading
     *
     * @param stdClass $newdefinition passfailrubric definition data
     *      as coming from gradingform_passfailrubric_editpassfailrubric::get_data()
     * @param int|null $usermodified optional userid of the author of the definition, defaults to the current user
     * @param boolean $doupdate if true actually updates DB, otherwise performs a check
     *
     */
    public function update_or_check_passfailrubric(stdClass $newdefinition, $usermodified = null, $doupdate = false) {
        global $DB;

        // Firstly update the common definition data in the {grading_definition} table.
        if ($this->definition === false) {
            if (!$doupdate) {
                // If we create the new definition there is no such thing as re-grading anyway.
                return 5;
            }
            // If definition does not exist yet, create a blank one.
            // We need id to save files embedded in description.
            parent::update_definition(new stdClass(), $usermodified);
            parent::load_definition();
        }
        if (!isset($newdefinition->passfailrubric['options'])) {
            $newdefinition->passfailrubric['options'] = self::get_default_options();
        }
        $newdefinition->options = json_encode($newdefinition->passfailrubric['options']);
        $editoroptions = self::description_form_field_options($this->get_context());
        $newdefinition = file_postupdate_standard_editor($newdefinition, 'description', $editoroptions, $this->get_context(),
            'grading', 'description', $this->definition->id);

        // Reload the definition from the database.
        $currentdefinition = $this->get_definition(true);

        // Update passfailrubric data.
        $haschanges = array();
        if (empty($newdefinition->passfailrubric['criteria'])) {
            $newcriteria = array();
        } else {
            $newcriteria = $newdefinition->passfailrubric['criteria']; // New ones to be saved.
        }
        $currentcriteria = $currentdefinition->passfailrubric_criteria;
        $criteriafields = array('sortorder', 'description', 'descriptionformat');
        $levelfields = array('definition', 'definitionformat');
        foreach ($newcriteria as $id => $criterion) {
            // Get list of submitted levels.
            $levelsdata = array();
            if (array_key_exists('levels', $criterion)) {
                $levelsdata = $criterion['levels'];
            }
            if (preg_match('/^NEWID\d+$/', $id)) {
                // Insert criterion into DB.
                $data = array('definitionid' => $this->definition->id, 'descriptionformat' => FORMAT_MOODLE);
                foreach ($criteriafields as $key) {
                    if (array_key_exists($key, $criterion)) {
                        $data[$key] = $criterion[$key];
                    }
                }
                if ($doupdate) {
                    $id = $DB->insert_record('gradingform_pfrbric_criteria', $data);
                }
                $haschanges[5] = true;
            } else {
                // Update criterion in DB.
                $data = array();
                foreach ($criteriafields as $key) {
                    if (array_key_exists($key, $criterion) && $criterion[$key] != $currentcriteria[$id][$key]) {
                        $data[$key] = $criterion[$key];
                    }
                }
                if (!empty($data)) {
                    // Update only if something is changed.
                    $data['id'] = $id;
                    if ($doupdate) {
                        $DB->update_record('gradingform_pfrbric_criteria', $data);
                    }
                    $haschanges[1] = true;
                }
                // Remove deleted levels from DB for this criteria.
                foreach ($currentcriteria[$id]['levels'] as $levelid => $currentlevel) {
                    if (!array_key_exists($levelid, $levelsdata)) {
                        if ($doupdate) {
                            $DB->delete_records('gradingform_pfrbric_levels', array('id' => $levelid));
                        }
                        $haschanges[4] = true;
                    }
                }
            }
            foreach ($levelsdata as $levelid => $level) {
                if (preg_match('/^NEWID\d+$/', $levelid)) {
                    // Insert level into DB.
                    $data = array('criterionid' => $id, 'definitionformat' => FORMAT_MOODLE);
                    foreach ($levelfields as $key) {
                        if (array_key_exists($key, $level)) {
                            $data[$key] = $level[$key];
                        }
                    }
                    /* @todo The levels table is not required, commenting out is the first step towards deleting it
                    * simply dropping the table breaks some other code.
                    $levelid = $DB->insert_record('gradingform_pfrbric_levels', $data);
                    */
                    $haschanges[3] = true;
                } else {
                    // Update level in DB.
                    $data = array();
                    foreach ($levelfields as $key) {
                        if (array_key_exists($key, $level) && $level[$key] != $currentcriteria[$id]['levels'][$levelid][$key]) {
                            $data[$key] = $level[$key];
                        }
                    }
                    if (!empty($data)) {
                        // Update only if something is changed.
                        $data['id'] = $levelid;
                        if ($doupdate) {
                            $DB->update_record('gradingform_pfrbric_levels', $data);
                        }
                        $haschanges[1] = true;
                    }
                }
            }
        }
        // Remove deleted criteria from DB.
        foreach (array_keys($currentcriteria) as $id) {
            if (!array_key_exists($id, $newcriteria)) {
                if ($doupdate) {
                    $DB->delete_records('gradingform_pfrbric_criteria', array('id' => $id));
                    $DB->delete_records('gradingform_pfrbric_levels', array('criterionid' => $id));
                }
                $haschanges[3] = true;
            }
        }
        foreach (array('status', 'description', 'descriptionformat', 'name', 'options') as $key) {
            if (isset($newdefinition->$key) && $newdefinition->$key != $this->definition->$key) {
                $haschanges[1] = true;
            }
        }
        if ($usermodified && $usermodified != $this->definition->usermodified) {
            $haschanges[1] = true;
        }
        if (!count($haschanges)) {
            return 0;
        }
        if ($doupdate) {
            parent::update_definition($newdefinition, $usermodified);
            $this->load_definition();
        }
        // Return the maximum level of changes.
        $changelevels = array_keys($haschanges);
        sort($changelevels);
        return array_pop($changelevels);
    }

    /**
     * Marks all instances filled with this passfailrubric with the status INSTANCE_STATUS_NEEDUPDATE
     */
    public function mark_for_regrade() {
        global $DB;
        if ($this->has_active_instances()) {
            $conditions = array('definitionid'  => $this->definition->id,
                        'status'  => gradingform_instance::INSTANCE_STATUS_ACTIVE);
            $DB->set_field('grading_instances', 'status', gradingform_instance::INSTANCE_STATUS_NEEDUPDATE, $conditions);
        }
    }

    /**
     * Loads the passfailrubric form definition if it exists
     *
     * There is a new array called 'passfailrubric_criteria' appended to the list of parent's definition properties.
     */
    protected function load_definition() {
        global $DB;
        $sql = "SELECT gd.*,
                       rc.id AS rcid,
                       rc.sortorder AS rcsortorder,
                       rc.description AS rcdescription,
                       rc.descriptionformat AS rcdescriptionformat,
                       rl.id AS rlid,
                       rl.definition AS rldefinition,
                       rl.definitionformat AS rldefinitionformat
                  FROM {grading_definitions} gd
             LEFT JOIN {gradingform_pfrbric_criteria} rc ON (rc.definitionid = gd.id)
             LEFT JOIN {gradingform_pfrbric_levels} rl ON (rl.criterionid = rc.id)
                 WHERE gd.areaid = :areaid AND gd.method = :method
              ORDER BY rc.sortorder";
        $params = array('areaid' => $this->areaid, 'method' => $this->get_method_name());

        $rs = $DB->get_recordset_sql($sql, $params);
        $this->definition = false;
        foreach ($rs as $record) {
            // Pick the common definition data.
            if ($this->definition === false) {
                $this->definition = new stdClass();
                foreach (array('id', 'name', 'description', 'descriptionformat', 'status', 'copiedfromid',
                        'timecreated', 'usercreated', 'timemodified', 'usermodified', 'timecopied', 'options') as $fieldname) {
                    $this->definition->$fieldname = $record->$fieldname;
                }
                $this->definition->passfailrubric_criteria = array();
            }
            // Pick the criterion data.
            if (!empty($record->rcid) and empty($this->definition->passfailrubric_criteria[$record->rcid])) {
                foreach (array('id', 'sortorder', 'description', 'descriptionformat') as $fieldname) {
                    $this->definition->passfailrubric_criteria[$record->rcid][$fieldname] = $record->{'rc'.$fieldname};
                }
                $this->definition->passfailrubric_criteria[$record->rcid]['levels'] = array();
            }
            // Pick the level data.
            if (!empty($record->rlid)) {
                foreach (array('id', 'definition', 'definitionformat') as $fieldname) {
                    $value = $record->{'rl'.$fieldname};
                    $this->definition->passfailrubric_criteria[$record->rcid]['levels'][$record->rlid][$fieldname] = $value;
                }
            }
        }
        if (isset($this->definition->passfailrubric_criteria)) {
            $this->get_grading_levels();
        }
        $rs->close();
    }

    public function get_grading_levels() {
        /* poking hard coded grading levels to overwrite what was taken from the db */
        $levels = [
            self::REFER_GRADE => ['id' => self::REFER_GRADE,
            'definition' => get_string('refer', 'gradingform_passfailrubric'), 'definitionformat' => 0],
            self::FAIL_GRADE => ['id' => self::FAIL_GRADE,
            'definition' => get_string('fail', 'gradingform_passfailrubric'), 'definitionformat' => 0],
            self::PASS_GRADE => ['id' => self::PASS_GRADE,
            'definition' => get_string('pass', 'gradingform_passfailrubric'),
             'definitionformat' => 0]
        ];

        if ($this->definition->passfailrubric_criteria) {
            foreach ($this->definition->passfailrubric_criteria as $key => $v) {
                $this->definition->passfailrubric_criteria[$key]['levels'] = $levels;
            }
        }
    }
    /**
     * Returns the default options for the passfailrubric display
     *
     * @return array
     */
    public static function get_default_options() {
        $options = array(
            'showdescriptionteacher' => 0,
            'alwaysshowdefinition' => 1,
            'showremarksstudent' => 1,
            'showdescriptionstudent' => 1
        );
        return $options;
    }

    /**
     * Gets the options of this passfailrubric definition, fills the missing options with default values
     *
     * @return array
     */
    public function get_options() {
        $options = self::get_default_options();
        if (!empty($this->definition->options)) {
            $thisoptions = json_decode($this->definition->options);
            foreach ($thisoptions as $option => $value) {
                $options[$option] = $value;
            }
        }
        return $options;
    }

    /**
     * Converts the current definition into an object suitable for the editor form's set_data()
     *
     * @param boolean $addemptycriterion whether to add an empty criterion if the passfailrubric
     *      is completely empty (just being created)
     * @return stdClass
     */
    public function get_definition_for_editing($addemptycriterion = false) {

        $definition = $this->get_definition();
        $properties = new stdClass();
        $properties->areaid = $this->areaid;
        if ($definition) {
            foreach (array('id', 'name', 'description', 'descriptionformat', 'status') as $key) {
                $properties->$key = $definition->$key;
            }
             $options = self::description_form_field_options($this->get_context());
            $properties = file_prepare_standard_editor($properties, 'description', $options, $this->get_context(),
                'grading', 'description', $definition->id);
        }
        if (!empty($definition->passfailrubric_criteria)) {
            $properties->passfailrubric['criteria'] = $definition->passfailrubric_criteria;
        } else if (!$definition && $addemptycriterion) {
            $properties->passfailrubric['criteria'] = array('addcriterion' => 1);
        }

        return $properties;
    }

    /**
     * Returns the form definition suitable for cloning into another area
     *
     * @see parent::get_definition_copy()
     * @param gradingform_controller $target the controller of the new copy
     * @return stdClass definition structure to pass to the target's {@link update_definition()}
     */
    public function get_definition_copy(gradingform_controller $target) {

        $new = parent::get_definition_copy($target);
        $old = $this->get_definition_for_editing();
        $new->description_editor = $old->description_editor;
        $new->passfailrubric = array('criteria' => array(), 'options' => $old->passfailrubric['options']);
        $newcritid = 1;
        $newlevid = 1;
        foreach ($old->passfailrubric['criteria'] as $oldcritid => $oldcrit) {
            unset($oldcrit['id']);
            if (isset($oldcrit['levels'])) {
                foreach ($oldcrit['levels'] as $oldlevid => $oldlev) {
                    unset($oldlev['id']);
                    $oldcrit['levels']['NEWID'.$newlevid] = $oldlev;
                    unset($oldcrit['levels'][$oldlevid]);
                    $newlevid++;
                }
            } else {
                $oldcrit['levels'] = array();
            }
            $new->passfailrubric['criteria']['NEWID'.$newcritid] = $oldcrit;
            $newcritid++;
        }

        return $new;
    }

    /**
     * Options for displaying the passfailrubric description field in the form
     *
     * @param object $context
     * @return array options for the form description field
     */
    public static function description_form_field_options($context) {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_user_max_upload_file_size($context, $CFG->maxbytes),
            'context'  => $context,
        );
    }

    /**
     * Formats the definition description for display on page
     *
     * @return string
     */
    public function get_formatted_description() {
        if (!isset($this->definition->description)) {
            return '';
        }
        $context = $this->get_context();

        $options = self::description_form_field_options($this->get_context());
        $description = file_rewrite_pluginfile_urls($this->definition->description, 'pluginfile.php', $context->id,
            'grading', 'description', $this->definition->id, $options);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->definition->descriptionformat, $formatoptions);
    }

    /**
     * Returns the passfailrubric plugin renderer
     *
     * @param moodle_page $page the target page
     * @return gradingform_passfailrubric_renderer
     */
    public function get_renderer(moodle_page $page) {
        return $page->get_renderer('gradingform_'. $this->get_method_name());
    }

    /**
     * Returns the HTML code displaying the preview of the grading form
     *
     * @param moodle_page $page the target page
     * @return string
     */
    public function render_preview(moodle_page $page) {

        if (!$this->is_form_defined()) {
            throw new coding_exception('It is the caller\'s responsibility to make sure that the form is actually defined');
        }

        $criteria = $this->definition->passfailrubric_criteria;
        $options = $this->get_options();
        $passfailrubric = '';
        $showdescription = false;
        if (has_capability('moodle/grade:managegradingforms', $page->context)) {
            $showdescription = true;
        } else {
            if (empty($options['alwaysshowdefinition'])) {
                // Ensure we don't display unless show passfailrubric option enabled.
                return '';
            }
        }
        $output = $this->get_renderer($page);
        if ($showdescription) {
            $passfailrubric .= $output->box($this->get_formatted_description(),
                    'gradingform_passfailrubric-description');
        }
        if (has_capability('moodle/grade:managegradingforms', $page->context)) {
            $passfailrubric .= $output->display_passfailrubric($criteria, $options,
                    self::DISPLAY_PREVIEW, 'passfailrubric');
        } else {
            $passfailrubric .= $output->display_passfailrubric($criteria, $options,
                    self::DISPLAY_PREVIEW_GRADED, 'passfailrubric');
        }

        return $passfailrubric;
    }

    /**
     * Deletes the passfailrubric definition and all the associated information
     */
    protected function delete_plugin_definition() {
        global $DB;

        // Get the list of instances.
        $instances = array_keys($DB->get_records('grading_instances', array('definitionid' => $this->definition->id), '', 'id'));
        // Delete all fillings.
        $DB->delete_records_list('gradingform_pfrbric_fillings', 'instanceid', $instances);
        // Delete all grades.
        $DB->delete_records_list('gradingform_pfrbric_grades', 'instanceid', $instances);
        // Delete instances.
        $DB->delete_records_list('grading_instances', 'id', $instances);
        // Get the list of criteria records.
        $criteria = array_keys($DB->get_records('gradingform_pfrbric_criteria',
                array('definitionid' => $this->definition->id), '', 'id'));
        // Delete levels.
        $DB->delete_records_list('gradingform_pfrbric_levels', 'criterionid', $criteria);
        // Delete critera.
        $DB->delete_records_list('gradingform_pfrbric_criteria', 'id', $criteria);
    }

    /**
     * If instanceid is specified and grading instance exists and it is created by this rater for
     * this item, this instance is returned.
     * If there exists a draft for this raterid+itemid, take this draft (this is the change from parent)
     * Otherwise new instance is created for the specified rater and itemid
     *
     * @param int $instanceid
     * @param int $raterid
     * @param int $itemid
     * @return gradingform_instance
     */
    public function get_or_create_instance($instanceid, $raterid, $itemid) {
        global $DB;
        if ($instanceid &&
                $instance = $DB->get_record('grading_instances',
                        array('id'  => $instanceid, 'raterid' => $raterid, 'itemid' => $itemid), '*', IGNORE_MISSING)) {
            return $this->get_instance($instance);
        }
        if ($itemid && $raterid) {
            $params = array('definitionid' => $this->definition->id, 'raterid' => $raterid, 'itemid' => $itemid);
            if ($rs = $DB->get_records('grading_instances', $params, 'timemodified DESC', '*', 0, 1)) {
                $record = reset($rs);
                $currentinstance = $this->get_current_instance($raterid, $itemid);
                if ($record->status == gradingform_passfailrubric_instance::INSTANCE_STATUS_INCOMPLETE &&
                        (!$currentinstance || $record->timemodified > $currentinstance->get_data('timemodified'))) {
                    $record->isrestored = true;
                    return $this->get_instance($record);
                }
            }
        }
        return $this->create_instance($raterid, $itemid);
    }

    /**
     * Returns html code to be included in student's feedback.
     *
     * @param moodle_page $page
     * @param int $itemid
     * @param array $gradinginfo result of function grade_get_grades
     * @param string $defaultcontent default string to be returned if no active grading is found
     * @param boolean $cangrade whether current user has capability to grade in this context
     * @return string
     */
    public function render_grade($page, $itemid, $gradinginfo, $defaultcontent, $cangrade) {
        return $this->get_renderer($page)->display_instances($this->get_active_instances($itemid), $defaultcontent, $cangrade);
    }

    // Full-text search support.

    /**
     * Prepare the part of the search query to append to the FROM statement
     *
     * @param string $gdid the alias of grading_definitions.id column used by the caller
     * @return string
     */
    public static function sql_search_from_tables($gdid) {
        return " LEFT JOIN {gradingform_pfrbric_criteria} rc ON (rc.definitionid = $gdid)
                 LEFT JOIN {gradingform_pfrbric_levels} rl ON (rl.criterionid = rc.id)";
    }

    /**
     * Prepare the parts of the SQL WHERE statement to search for the given token
     *
     * The returned array cosists of the list of SQL comparions and the list of
     * respective parameters for the comparisons. The returned chunks will be joined
     * with other conditions using the OR operator.
     *
     * @param string $token token to search for
     * @return array
     */
    public static function sql_search_where($token) {
        global $DB;

        $subsql = array();
        $params = array();

        // Search in passfailrubric criteria description.
        $subsql[] = $DB->sql_like('rc.description', '?', false, false);
        $params[] = '%'.$DB->sql_like_escape($token).'%';

        // Search in passfailrubric levels definition.
        $subsql[] = $DB->sql_like('rl.definition', '?', false, false);
        $params[] = '%'.$DB->sql_like_escape($token).'%';

        return array($subsql, $params);
    }

    /**
     * @return array Array containing a single key/value pair with the 'passfailrubric_criteria' external_multiple_structure.
     * @see gradingform_controller::get_external_definition_details()
     * @since Moodle 2.5
     */
    public static function get_external_definition_details() {
        $passfailrubriccriteria = new external_multiple_structure(
            new external_single_structure(
                array(
                   'explanation' => new external_value(PARAM_TEXT, 'explanation', VALUE_OPTIONAL),
                   'ismoderated' => new external_value(PARAM_INT, 'ismoderated', VALUE_OPTIONAL),
                   'id' => new external_value(PARAM_INT, 'criterion id', VALUE_OPTIONAL),
                   'sortorder' => new external_value(PARAM_INT, 'sortorder', VALUE_OPTIONAL),
                   'description' => new external_value(PARAM_RAW, 'description', VALUE_OPTIONAL),
                   'descriptionformat' => new external_format_value('description', VALUE_OPTIONAL),
                   'levels' => new external_multiple_structure(
                                   new external_single_structure(
                                       array(
                                        'id' => new external_value(PARAM_INT, 'level id', VALUE_OPTIONAL),
                                        'definition' => new external_value(PARAM_RAW, 'definition', VALUE_OPTIONAL),
                                        'definitionformat' => new external_format_value('definition', VALUE_OPTIONAL)
                                       )
                                   ), 'levels', VALUE_OPTIONAL
                              )
                   )
              ), 'definition details', VALUE_OPTIONAL
        );
        return array('passfailrubric_criteria' => $passfailrubriccriteria);
    }

    /**
     * Returns an array that defines the structure of the passfailrubric's filling. This function is used by
     * the web service function core_grading_external::get_gradingform_instances().
     *
     * @return An array containing a single key/value pair with the 'criteria' external_multiple_structure
     * @see gradingform_controller::get_external_instance_filling_details()
     * @since Moodle 2.6
     */
    public static function get_external_instance_filling_details() {
        $criteria = new external_multiple_structure(
            new external_single_structure(
                array(
                    'grade-overrid' => new external_value(PARAM_INT, 'grade-override', VALUE_OPTIONAL),
                    'explanation' => new external_value(PARAM_TEXT, 'explanation', VALUE_OPTIONAL),
                    'ismoderated' => new external_value(PARAM_INT, 'ismoderated', VALUE_OPTIONAL),
                    'id' => new external_value(PARAM_INT, 'filling id'),
                    'criterionid' => new external_value(PARAM_INT, 'criterion id'),
                    'levelid' => new external_value(PARAM_INT, 'level id', VALUE_OPTIONAL)
                )
            ), 'filling', VALUE_OPTIONAL
        );
        return array ('criteria' => $criteria);
    }

}

/**
 * Class to manage one passfailrubric grading instance.
 *
 * Stores information and performs actions like update, copy, submit, etc.
 *
 * @package    gradingform_passfailrubric
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_passfailrubric_instance extends gradingform_instance {

    /** @var array stores the passfailrubric, has two keys: 'criteria' and 'options' */
    protected $passfailrubric;

    /**
     * Deletes this (INCOMPLETE) instance from database.
     */
    public function cancel() {
        global $DB;
        parent::cancel();
        $DB->delete_records('gradingform_pfrbric_fillings', array('instanceid' => $this->get_id()));
        $DB->delete_records('gradingform_pfrbric_grades', array('instanceid' => $this->get_id()));
    }

    /**
     * Duplicates the instance before editing (optionally substitutes raterid and/or itemid with
     * the specified values)
     *
     * @param int $raterid value for raterid in the duplicate
     * @param int $itemid value for itemid in the duplicate
     * @return int id of the new instance
     */
    public function copy($raterid, $itemid) {
        global $DB;
        $instanceid = parent::copy($raterid, $itemid);
        $currentgrade = $this->get_passfailrubric_filling();
        foreach ($currentgrade['criteria'] as $criterionid => $record) {
            $params = array(
                'instanceid' => $instanceid,
                'criterionid' => $criterionid,
                'levelid' => $record['levelid'],
                'remark' => $record['remark'],
                'remarkformat' => FORMAT_MOODLE,
            );
            $DB->insert_record('gradingform_pfrbric_fillings', $params);
        }
        return $instanceid;
    }

    /**
     * Retrieves from DB and returns the data how this passfailrubric was filled
     *
     * @param boolean $force whether to force DB query even if the data is cached
     * @return array
     */
    public function get_passfailrubric_filling($force = false) {
        global $DB;
        if ($this->passfailrubric === null || $force) {
            $records = $DB->get_records('gradingform_pfrbric_fillings', array('instanceid' => $this->get_id()));
            $this->passfailrubric = array('criteria' => array());
            foreach ($records as $record) {
                $this->passfailrubric['criteria'][$record->criterionid] = (array)$record;
            }
        }
        $this->passfailrubric['grade'] = $this->get_grade();
        return $this->passfailrubric;
    }

    /**
     * Updates the instance with the data received from grading form. This function may be
     * called via AJAX when grading is not yet completed, so it does not change the
     * status of the instance.
     *
     * @param array $data
     */
    public function update($data) {
        global $DB, $USER;
        $currentgrade = $this->get_passfailrubric_filling();
        /* updates grading instances */
        parent::update($data);
        if (isset($data['criteria']) && $data['criteria']) {
            if (is_numeric($data['override-grade'])) {
                $instanceid = $this->get_data('id');
                $newrecord = [
                    'instanceid' => $instanceid,
                    'itemid' => $data['itemid'],
                    'grade' => $data['override-grade'],
                    'explanation' => $data['explanation'],
                    'authoredby' => $USER->id,
                    'timecreated' => time()
                ];
                /* stores grades where autocalc is overriden */
                $DB->insert_record('gradingform_pfrbric_grades', $newrecord);

            }
          if ($data['ismoderated'] =='on') {
              $newrecord = [
                'item' => $data['itemid'],
                'userid' => $USER->id,
                'timecreated' => time()
            ];
              $DB->insert_record('gradingform_pfrbric_moderate', $newrecord);
            } else {
              // Delete if moderate box was unchecked and current user had previously moderated.
              $condition = ['item'=>$data['itemid'],'userid'=>$USER->id];
              $id = $DB->get_field('gradingform_pfrbric_moderate', 'id', $condition);
              if ($id) {
                $DB->delete_records('gradingform_pfrbric_moderate', ['id'=>$id]);
              }

            }
            foreach ($data['criteria'] as $criterionid => $record) {
                /*if there is no grade/filling for this criteria, insert one */
                if (!array_key_exists($criterionid, $currentgrade['criteria'])) {
                    $newrecord = [
                        'instanceid' => $this->get_id(),
                        'criterionid' => $criterionid,
                        'levelid' => $record['levelid'],
                        'remarkformat' => FORMAT_MOODLE,
                        'score'  => $record['levelid'] // ...levelid as score looks odd but bare with me.
                    ];
                    if (isset($record['remark'])) {
                        $newrecord['remark'] = $record['remark'];
                    }
                     $DB->insert_record('gradingform_pfrbric_fillings', $newrecord);
                } else {
                    $newrecord = ['id' => $currentgrade['criteria'][$criterionid]['id'] ];
                    foreach (array('levelid', 'remark') as $key) {
                        if (isset($record[$key]) && $currentgrade['criteria'][$criterionid][$key] != $record[$key]) {
                            $newrecord[$key] = $record[$key];
                        }
                    }
                    if (count($newrecord) > 1) {
                        $DB->update_record('gradingform_pfrbric_fillings', $newrecord);
                    }
                }
            }
            foreach ($currentgrade['criteria'] as $criterionid => $record) {
                if (!array_key_exists($criterionid, $data['criteria'])) {
                    $DB->delete_records('gradingform_pfrbric_fillings', array('id' => $record['id']));
                }
            }
        } else {
            if (isset($currentgrade['criteria']) && $currentgrade['criteria']) {
                foreach ($currentgrade['criteria'] as $criteria) {
                    $DB->delete_records('gradingform_pfrbric_fillings', array('id' => $criteria['id']));
                }
            }
        }
        $this->get_passfailrubric_filling(true);
    }

    /**
     * Calculates the grade to be pushed to the gradebook
     * Cannot use self::CONSTANT_NAME because this gets called from parent class
     *
     * @return float|int the valid grade from $this->get_controller()->get_grade_range()
     */
    public function get_grade() {
        global $DB;
        /* check the value written on last update */
        $sql = 'SELECT grade FROM {gradingform_pfrbric_grades} where itemid=:itemid and instanceid=:instanceid';
        $gradeoverride = $DB->get_record_sql($sql, ['itemid' => $this->data->itemid, 'instanceid' => $this->data->id]);
        /* if the grade has been overriden, don't do the calculation, return the overriden value */
        if (isset($gradeoverride->grade)) {
            return $gradeoverride->grade;
        }
        $id = $this->get_id();

        $instance = ['instanceid' => $id];
        $scores = $DB->get_records('gradingform_pfrbric_fillings', $instance);
        $scorecount = count($scores);
        /* @todo invent a more elegant calculation of the grades (with an array?) */
        $passcount = $failcount = $refercount = 0;

        foreach ($scores as $s) {
            if ($s->levelid == gradingform_passfailrubric_controller::PASS_GRADE) {
                $passcount++;
            }
            if ($s->levelid == gradingform_passfailrubric_controller::FAIL_GRADE) {
                $failcount++;
            }
            if ($s->levelid == gradingform_passfailrubric_controller::REFER_GRADE) {
                $refercount++;
            }
        }

        if ($scorecount == 0) {
            /* Grade will show up as blank */
            return null;
        } else if ($passcount == $scorecount) {
            /* Every criteria is a pass */
            return gradingform_passfailrubric_controller::PASS_GRADE;
        } else if ($refercount == 0 && ($scorecount > 0)) {
            /* Not everything is a pass but there are no refers */
            return gradingform_passfailrubric_controller::FAIL_GRADE;
        } else if ($refercount > 0) {
            /* some criteria are set to refer */
            return gradingform_passfailrubric_controller::REFER_GRADE;
        } else {
            return null;
        }
    }

    /**
     * Returns html for form element of type 'grading'.
     *
     * @param moodle_page $page
     * @param MoodleQuickForm_grading $gradingformelement
     * @return string
     */
    public function render_grading_element($page, $gradingformelement) {
        $criteria = $this->get_controller()->get_definition()->passfailrubric_criteria;
        $options = $this->get_controller()->get_options();
        if (!$gradingformelement->_flagFrozen) {
            $module = array('name' => 'gradingform_passfailrubric',
                'fullpath' => '/grade/grading/form/passfailrubric/js/passfailrubric.js');
            $page->requires->js_init_call('M.gradingform_passfailrubric.init',
                    array(array(
                        'name' => $gradingformelement->getName(), 'criterion' => array_keys($criteria),
                    )), true, $module);
                    $mode = gradingform_passfailrubric_controller::DISPLAY_EVAL;
        } else {
            if ($gradingformelement->_persistantFreeze) {
                $mode = gradingform_passfailrubric_controller::DISPLAY_EVAL_FROZEN;
            } else {
                $mode = gradingform_passfailrubric_controller::DISPLAY_REVIEW;
            }
        }
        $value = $gradingformelement->getValue();
        $html = '';
        if ($value === null) {
            $value = $this->get_passfailrubric_filling();
        } else if (!$this->validate_grading_element($value)) {
            $html .= html_writer::tag('div', get_string('passfailrubricnotcompleted',
                    'gradingform_passfailrubric'), array('class' => 'gradingform_passfailrubric-error'));
        }
        $currentinstance = $this->get_current_instance();
        if ($currentinstance && $currentinstance->get_status() == gradingform_instance::INSTANCE_STATUS_NEEDUPDATE) {
            $html .= html_writer::tag('div', get_string('needregrademessage',
                    'gradingform_passfailrubric'), array('class' => 'gradingform_passfailrubric-regrade'));
        }
        $haschanges = false;
        if ($currentinstance) {
            $curfilling = $currentinstance->get_passfailrubric_filling();
            foreach ($curfilling['criteria'] as $criterionid => $curvalues) {
                $value['criteria'][$criterionid]['savedlevelid'] = $curvalues['levelid'];
                $newlevelid = null;
                if (isset($value['criteria'][$criterionid]['levelid'])) {
                    $newlevelid = $value['criteria'][$criterionid]['levelid'];
                }
                if ($newlevelid != $curvalues['levelid']) {
                    $haschanges = true;
                }
            }
        }
        if ($this->get_data('isrestored') && $haschanges) {
            $html .= html_writer::tag('div', get_string('restoredfromdraft',
                    'gradingform_passfailrubric'), array('class' => 'gradingform_passfailrubric-restored'));
        }

        $grademenu = $this->get_controller()->get_grade_range();

        $grade = ($currentinstance) ? $currentinstance->get_grade() : 0;
        $value['itemid'] = $this->data->itemid ?: 0;
        $html .= $this->get_controller()->get_renderer($page)->display_passfailrubric($criteria, $options, $mode,
                $gradingformelement->getName(), $value, $grademenu, $grade);
        return $html;
    }
}
