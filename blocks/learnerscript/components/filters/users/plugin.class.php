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
 * LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
use block_learnerscript\local\pluginbase;

class plugin_users extends pluginbase {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->singleselection = true;
        $this->placeholder = true;
        $this->maxlength = 0;
        $this->fullname = get_string('filterusers', 'block_learnerscript');
        $this->reporttypes = array('sql', 'userassignments', 'usercourses',
            'student_performance', 'uniquelogins', 'userquizzes', 'users',
            'student_overall_performance', 'topic_wise_performance', 'usersscorm');
    }

    public function summary($data) {
        return get_string('filterusers_summary', 'block_learnerscript');
    }

    public function execute($finalelements, $data) {

        $filterusers = optional_param('filter_users', 0, PARAM_RAW);
        if (!$filterusers) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filterusers);
        } else {
            if (preg_match("/%%FILTER_SYSTEMUSER:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filterusers;
                return str_replace('%%FILTER_SYSTEMUSER:' . $output[1] . '%%', $replace,
                    $finalelements);
            }
        }
        return $finalelements;
    }
    public function filter_data($selectoption = true){
            global $DB;
        $usersoptions = (new \block_learnerscript\local\querylib)->filter_get_users($this,
                        $selectoption);
            return $usersoptions;
        }
    public function print_filter(&$mform) {
        $usersoptions = $this->filter_data();
        if(!$this->placeholder){
            unset($usersoptions[0]);
        }
        $select = $mform->addElement('select', 'filter_users', null, $usersoptions,
                    array('data-select2' => 1,
                          'data-maximum-selection-length' => $this->maxlength));

        if (!$this->singleselection) {
            $select->setMultiple(true);
        }
        if ($this->required) {
            if (!empty(array_keys($usersoptions)[1])) {
                $select->setSelected(array_keys($usersoptions)[1]);
            }
        }
        $select->setHiddenLabel(true);
        $mform->setType('filter_users', PARAM_INT);
    }
}