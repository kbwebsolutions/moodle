<?php

namespace local_tlcore\exceptions;

use moodle_exception;

class http_exception extends moodle_exception {
  public function __construct($message) {
    parent::__construct('http_exception', 'local_tlcore', null, $message);
  }
}