<?php

namespace local_tlcore;

use local_tlcore\exceptions\routing_exception;
use moodle_exception;

abstract class router {
  public function __construct() {
    $this->init();
  }

  public function dispatch() {
    $request_method = $this->get_request_method();
    $action = $this->get_action();
    $method = "{$request_method}_{$action}";
    if (!method_exists($this, $method)) {
      throw new routing_exception("method `{$method}` not found");
    }

    return $this->$method();
  }

  public function get_request_method() {
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    return strtolower($method);
  }

  public function get_action() {
    $action = required_param('action', PARAM_ALPHANUMEXT);
    return strtolower($action);
  }

  /**
   * Generic method for fetching a capability for the current user.
   * @return array ['result' => bool]
   */
  protected function get_has_capability() {
    $cap      = required_param('cap', PARAM_TEXT);
    $context  = optional_param('context', 'system', PARAM_ALPHANUM);
    $id       = optional_param('id', null, PARAM_INT);

    if ($context) {
      $class = "context_{$context}";
      $instance = $class::instance($id);
    } else {
      throw new moodle_exception('A context is needed');
    }

    return ['result' => has_capability($cap, $instance)];
  }

  protected function init() {
    // hook
  }
}