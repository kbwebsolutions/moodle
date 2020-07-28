<?php

namespace local_tlcore\output;

use context_system;
use moodle_exception;
use moodle_page;

class rest_renderer extends ajax_renderer {
    /**
     * The rest renderer sets up a basic page for us
     * so we don't have to mess about with $PAGE in the
     * rest endpoint.
     */
    public function __construct() {
        global $CFG, $PAGE; /** @var moodle_page $PAGE */
        $context = context_system::instance();
        $PAGE->set_context($context);

        require_once($CFG->dirroot.'/local/tlcore/lib.php');
        parent::__construct($PAGE, null);
        $this->header();
    }

    public function call_action($class) {
        $action = $this->get_action();
        $body = $this->get_body() ?: [];

        if (method_exists($class, $action)) {
            return $class::$action($body, $this) ?: [];
        } else {
            throw new moodle_exception('unknown_action', 'local_tlcore', ['action' => $action]);
        }
    }

    public function get_action() {
        return required_param('action', PARAM_ALPHANUMEXT);
    }
}