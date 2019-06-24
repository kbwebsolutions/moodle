<?php

use local_tlcore\actions;
use core\session\manager as session_manager;

$entrypoint = !defined('MOODLE_INTERNAL');

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/../../vendor/autoload.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/authlib.php');

header('Content-Type: application/json');

set_exception_handler(function($e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage(), 'exception' => (array)$e]);
    exit;
});

$action = required_param('action', PARAM_ALPHANUMEXT);
$body = json_decode(file_get_contents('php://input'), true);

$token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

if ($token) {
    if ($token !== 'Moodle') { //get_config('local_tlcore', 'webservice_token')) {
        throw new moodle_exception('not_authorized', 'local_tlcore');
    }

    $user = $DB->get_record('user', ['id' => 2], '*', MUST_EXIST);
    session_manager::init_empty_session();
    session_manager::set_user($user); /** @todo might not be enough for login, check */
    login_attempt_valid($user);
}

if ($entrypoint) {
    if (method_exists(actions::class, $action)) {
        $out = actions::$action($body) ?: [];
    } else {
        throw new moodle_exception('unknown_action', 'local_tlcore', ['action' => $action]);
    }

    echo json_encode($out);
    exit;
}