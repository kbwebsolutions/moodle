<?php

namespace local_tlcore\exceptions;

use moodle_exception;

class database_exception extends moodle_exception {
  public function __construct($message) {
    parent::__construct('database_exception', 'local_tlcore', null, $message);
  }
}