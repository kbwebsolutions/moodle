<?php

if (!defined('MOODLE_INTERNAL')) {
    die(get_string('nodirectaccess','block_learnerscript'));    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');
use block_learnerscript\local\ls;
class combination_form extends moodleform {

    function definition() {
        global $DB, $USER, $CFG;

        $mform = & $this->_form;

        $report = $this->_customdata['report'];


            $components = (new ls)->cr_unserialize($this->_customdata['report']->components);

            if (!is_array($components) || empty($components['columns']['elements']))
                print_error('nocolumns');

            $columns = $components['columns']['elements'];
            foreach ($columns as $c) {
               $options[$c['formdata']->columname] = $c['formdata']->columname;
            }

        // $mform->addElement('header', 'crformheader', get_string('combination', 'block_learnerscript'), '');

        $mform->addElement('text', 'chartname', get_string('chartname', 'block_learnerscript'));
        $mform->setType('chartname', PARAM_RAW);
        $mform->addRule('chartname', null, 'required', null, 'client');
        foreach($components['plot']['elements'] as $plotreport){
             if(!in_array($plotreport['pluginname'],array('combination','bar'))) {
                $listofcharts[$plotreport['id']]=$plotreport['formdata']->chartname;
             }
        }
        $mform->addElement('select', 'lsitofcharts', get_string('listofcharts', 'block_learnerscript'),$listofcharts,array('multiple'=>'multiple'));
        $mform->addRule('lsitofcharts', null, 'required', null, 'client');
        // buttons
        $this->add_action_buttons(true, get_string('add'));
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

}
