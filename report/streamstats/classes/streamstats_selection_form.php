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
 * @package    report
 * @subpackage streamstats
 * @copyright  2019 Chartered College of Teaching
 * @author     Kieran Briggs <kbriggs@chartered.college>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class report_streamstats_selection_form extends moodleform
{
    /**
     * Form definition method.
     */
    public function definition() {

        $mform = $this->_form;
        $componentarray = $this->_customdata['components'];

        $mform->addElement('header', 'displayinfo', get_string('mystats', 'report_streamstats'));


        $mform->addElement('select', 'eventcomponent', get_string('choosestream', 'report_streamstats'), $componentarray);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('button', 'filterbutton', get_string('run', 'report_streamstats'));
        $mform->addGroup($buttonarray, 'filterbuttons', '', array(' '), false);
    }
}