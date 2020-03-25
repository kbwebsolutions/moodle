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
 * @package    filter
 * @subpackage urlresource
 * @copyright  2014 Andreas Wagner, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/lib/formslib.php');

class urlreplaceedit_form extends moodleform {

    // Define the form.
    protected function definition() {
        global $PAGE;

        $mform = & $this->_form;
        $url = $this->_customdata['url'];
        $course = $this->_customdata['course'];
        $urlreplacedata = $this->_customdata['urlreplacedata'];

        $cangrabnew = true;

        if ($cangrabnew) {

            $mform->addElement('text', 'externalurl', get_string('externalurl', 'filter_urlresource'));
            $mform->setType('externalurl', PARAM_URL);
            $mform->setDefault('externalurl', $this->_customdata['externalurl']);
        } else {

            $mform->addElement('hidden', 'externalurl');
            $mform->setType('externalurl', PARAM_URL);
            $mform->setDefault('externalurl', $this->_customdata['externalurl']);
        }

        $content = html_writer::tag('div', $url, array('class' => 'fur-url'));
        $mform->addElement('static', 'surl', get_string('url', 'filter_urlresource'), $content);

        $mform->addElement('text', 'title', get_string('title', 'filter_urlresource'));
        $mform->setType('title', PARAM_TEXT);
        $mform->setDefault('title', $urlreplacedata->title);

        $config = get_config('filter_urlresource');

        $params = array(
            'src' => $urlreplacedata->imgurl, 'id' => 'imgpreview',
            'style' => 'max-width:' . $config->imgmaxwidth . 'px'
        );
        $content = html_writer::tag('div', html_writer::empty_tag('img', $params));
        $mform->addElement('static', '', html_writer::tag('button', get_string('refreshimages', 'filter_urlresource'), array('id' => 'refreshimages')));

        $mform->addElement('static', 'img', get_string('image', 'filter_urlresource') .
                html_writer::tag('button', '<', array('id' => 'previmage')) .
                html_writer::tag('button', '>', array('id' => 'nextimage')), $content);

        $mform->addElement('static', 'desc', '', html_writer::tag('div', $urlreplacedata->imgurl, array('id' => 'imgsrc')));

        // Img-URL to retrieve.
        $mform->addElement('hidden', 'imgurl', '', array('id' => 'imgurl'));
        $mform->setType('imgurl', PARAM_URL);
        $mform->setDefault('imgurl', $urlreplacedata->imgurl);

        // URL to retrieve.
        $mform->addElement('hidden', 'url');
        $mform->setType('url', PARAM_URL);
        $mform->setDefault('url', $this->_customdata['url']);

        // Id of course, we are in.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $course->id);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', $this->_customdata['cmid']);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('submit'));
        $buttonarray[] = $mform->createElement('submit', 'deletebutton', get_string('delete'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $args = (array) $urlreplacedata;
        $args['courseid'] = $course->id;
        $args['urlimgs'] = $this->_customdata['urlimgs'];
        $args['minwidth'] = FILTER_URLRESOURCE_MINIMGWIDTH;

        $PAGE->requires->strings_for_js(array('noimgavailable'), 'filter_urlresource');
        $PAGE->requires->yui_module(
                'moodle-filter_urlresource-urlreplaceform', 'M.filter_urlresource.urlreplaceforminit', array($args), null, true);
    }

}