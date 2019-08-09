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

use block_learnerscript\local\reportbase;
defined('MOODLE_INTERNAL') || die();
class report_categories extends reportbase {

    public function init() {
        global $USER;
        parent::__construct($report);
        $this->components = array('columns', 'conditions', 'filters', 'permissions', 'calcs', 'plot');
        $this->columns = array('categoryfield' => ['categoryfield']);
        $this->courselevel = true;
        $this->parent = false;
    }

    public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
        global $DB;
        $params = array();
        $concatsql = " ";
        if (isset($this->search) && $this->search) {
            $fields = array("name", "description", "parent");
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $concatsql = " AND ($fields) ";
        }
        if ($this->conditionsenabled) {
            $conditions = implode(',', $conditionfinalelements);
            if (empty($conditions)) {
                return array(array(), 0);
            }
            $concatsql .= " AND id IN ( $conditions )";
        }
        if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $concatsql .= " AND timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        $elements = array();
        $categoriescount = "SELECT COUNT(id) FROM {course_categories} WHERE 1 = 1 AND visible = 1 $concatsql";
        $sql = "SELECT * FROM {course_categories} WHERE 1 = 1 AND visible = 1 $concatsql";
        try {
            $totalcategories = $DB->count_records_sql($categoriescount, $params);
        } catch (dml_exception $e) {
            $totalcategories = 0;
        }
        if (!empty($this->sqlorder)) {
            $sql .= " ORDER BY ". $this->sqlorder;
        } else {
            if (!empty($sqlorder)) {
                $sql .= " ORDER BY $sqlorder ";
            } else {
                $sql .= " ORDER BY id DESC ";
            }
        }
        try {
            $categorylist = $DB->get_records_sql($sql, $params, $this->start, $this->length);
        } catch (dml_exception $e) {
            $categorylist = array();
        }
        return array($categorylist, $totalcategories);
    }

    public function get_rows($elements, $sqlorder = '') {
        return $elements;
    }

}
