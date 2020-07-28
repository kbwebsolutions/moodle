<?php

namespace local_tlcore\exceptions;

use moodle_exception;

class routing_exception extends moodle_exception {
  public function __construct($message) {
    parent::__construct('routing_exception', 'local_tlcore', null, $message);
  }
}