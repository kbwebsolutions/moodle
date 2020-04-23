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
 * Steps definitions for rubrics.
 *
 * @package   gradingform_passfailrubric
 * @category  test
 * @copyright 2020 Titus Learning by Marcus Green
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Steps definitions to help with passfailrubric.
 *
 * @package   gradingform_passfailrubric
 * @category  test
 * @copyright 2020 Titus Learning by Marcus Green
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_gradingform_passfailrubric extends behat_base {

    /**
     * @Given I grade by filling the passfailrubric with:
     */
    public function i_grade_by_filling_the_passfailrubric_with(TableNode $passfailrubric) {
        $levels = ['notmet' => 1, 'partiallymet' => 2, 'met' => 3];
        $criteria = $passfailrubric->getRowsHash();
        foreach ($criteria as $name => $criterion) {
            $path = "//td[contains(@class,'description criteria-description')][text()='$name']";
            if ($criteriodtd = $this->find('xpath', $path)) {
                $criteriontdname = $criteriodtd->getAttribute('id');
                if ($idparts = explode('-', $criteriontdname)) {
                    $criteriaid = $idparts[2];
                }
                if ($criteriaid) {
                    $level = $levels[$criterion[0]];
                    $remark = $criterion[1];
                    $radiobutton = "//input[@name='advancedgrading[criteria][" . $criteriaid . "][levelid]'][@value='$level']";
                    $this->execute('behat_general::i_click_on', [$radiobutton, 'xpath_element']);
                    $criterionroot = 'advancedgrading[criteria]' . '[' . $criteriaid . ']';
                    $this->execute('behat_forms::i_set_the_field_to', [$criterionroot . '[remark]', $remark]);
                }
            }
        }
    }
}