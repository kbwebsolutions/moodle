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
 * Contains the helper class for the select missing words question type tests.
 *
 * @package    gradingform_passfailrubric
 * @copyright  2019 Titus LearningMarcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/grade/grading/form/passfailrubric/passfailrubriceditor.php');
require_once($CFG->dirroot . '/grade/grading/form/passfailrubric/renderer.php');
require_once($CFG->dirroot . '/grade/grading/form/passfailrubric/lib.php');

require_once($CFG->dirroot . '/lib/pagelib.php');

/**
 * Main class for testing the PassFailRubric grading plugin
 * @package    gradingform_passfailrubric
 * @copyright  2019 Titus Learning by Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class passfail_test extends basic_testcase {

    public function test_created_form() {

        $form = new MoodleQuickForm_passfailrubriceditor('testpassfaileditor', 'elementlabel', null);
        $type = $form->getElementTemplateType();
        $this->assertEquals('default', $type);
    }

    public function test_grade_calculation() {
      /**
       * from lib.php
     * Calculates the grade to be pushed to the gradebook
     * Cannot use self::CONSTANT_NAME because this gets called from parent class
     *
     * @return float|int the valid grade from $this->get_controller()->get_grade_range()
     */

        $criteria = [];
        $controller = '';
        $data = '';
        $form = new gradingform_passfailrubric_instance($controller, $data);

    }

}