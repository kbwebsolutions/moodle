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
use block_learnerscript\local\ls;

class plugin_coursecategories extends pluginbase {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercoursecategories', 'block_learnerscript');
        $this->reporttypes = array('courses', 'sql', 'statistics');
    }

    public function summary($data) {
        return get_string('filtercoursecategories_summary', 'block_learnerscript');
    }

    public function execute($finalelements, $data, $filters) {
        $fcategory = isset($filters['filter_coursecategories']) ? $filters['filter_coursecategories'] : null;
        $filtercategory = optional_param('filter_coursecategories', $fcategory, PARAM_INT);
        if (!$filtercategory) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtercategory);
        } else {
            if (preg_match("/%%FILTER_COURSECATEGORIESSUBIDS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filtercategory;
                return str_replace('%%FILTER_COURSECATEGORIESSUBIDS:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }
    public function filter_data($selectoption = true){
        $categoryoptions = (new \block_learnerscript\local\querylib)->filter_get_category($this, $selectoption); 
        return $categoryoptions;
    }
    public function print_filter(&$mform) {

        $categoryoptions = $this->filter_data();
        if(!$this->placeholder){
            unset($categoryoptions[0]);
        }
        $select = $mform->addElement('select', 'filter_coursecategories', null, $categoryoptions,array('data-select2'=>true, 'data-maximum-selection-length' => $this->maxlength));
        $select->setHiddenLabel(true);
        if($this->required){
            if (!empty(array_keys($categoryoptions)[1])) {
                $select->setSelected(array_keys($categoryoptions)[1]);
            }
        }
        $mform->setType('filter_coursecategories', PARAM_INT);
    }

}
