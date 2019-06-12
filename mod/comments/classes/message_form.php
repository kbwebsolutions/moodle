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
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage comments
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/comments/lib.php');

class mod_comments_message_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $cid = $this->_customdata['modid'];

        $mform =& $this->_form;
       
        // Message area
        $mform->addElement('textarea', 'posting', get_string('details', 'comments'), 'wrap="virtual" rows="3" cols="100"');
        $mform->setType('details_editor', PARAM_RAW);
        $mform->addHelpButton('posting', 'details', 'bookingform');

        $mform->addElement('hidden', 'id', $cid);

        // Submit button
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('post', 'comments'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'page_actions','',array(''), false);
        
    }
}