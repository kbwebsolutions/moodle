<?php

namespace local_tlcore;

use advanced_testcase; // import PHPUnit testcase

defined('MOODLE_INTERNAL') || die();
require_once( dirname(__DIR__).'/lib.php' );

abstract class testcase extends advanced_testcase {
  const SKIP = false;

  protected $email_sink;

  /**
   * Call this method to start capture of emails in the testcase.
   * @return void 
   */
  protected function email_intercept() {
    global $CFG;
    unset_config('noemailever');
    $this->email_sink = $this->redirectEmails();
  }

  /**
   * Gets captured emails in the testcase.
   * @return array Array of messages
   */
  protected function get_emails() {
    if (!$this->email_sink) return [];
    return array_map(function($a) {
      return (array)$a;
    }, $this->email_sink->get_messages());
  }

  /**
   * Convenience method for testing that var is an array.
   * @param  mixed $var Var to test
   * @return bool       True if assertion passes
   */
  public function assertArray($var = null, $comment = '') {
    $this->assertInternalType('array', $var, $comment);
    return true;
  }
  
  /**
   * Convenience method for testing that var is a bool.
   * @param  mixed $var Var to test
   * @return bool       True if assertion passes
   */
  public function assertBool($var = null, $comment = '') {
    $this->assertInternalType('bool', $var, $comment);
    return true;
  }

  /**
   * Convenience method for testing that var is a float.
   * @param  mixed $var Var to test
   * @return bool       True if assertion passes
   */
  public function assertFloat($var = null, $comment = '') {
    $this->assertInternalType('float', $var, $comment);
    return true;
  }

  /**
   * Convenience method for testing that var is an int.
   * @param  mixed $var Var to test
   * @return bool       True if assertion passes
   */
  public function assertInt($var = null, $comment = '') {
    $this->assertInternalType('int', $var, $comment);
    return true;
  }

  /**
   * Convenience method for testing that var is an object.
   * @param  mixed $var Var to test
   * @return bool       True if assertion passes
   */
  public function assertObject($var = null, $comment = '') {
    $this->assertInternalType('object', $var, $comment);
    return true;
  }

  /**
   * Convenience method for testing that var is an string.
   * @param  mixed $var Var to test
   * @return bool       True if assertion passes
   */
  public function assertString($var = null, $comment = '') {
    $this->assertInternalType('string', $var, $comment);
    return true;
  }

  /**
   * When running unit tests, Moodle sets $CFG->wwwroot
   * to "http://www.example.com/moodle".  Get the proper one.
   * @return string $CFG->wwwroot scraped from config.php
   */
  protected function get_real_wwwroot() {
    $url = '';
    $lines = file(__DIR__.'/../../../config.php');
    $match = '';
    foreach ($lines as $line) {
      if (preg_match('/\$CFG->wwwroot.+\'([^\']*)\'/', $line, $match)) {
        $url = isset($match[1]) ? $match[1] : '';
        break;
      }
    }
    return $url;
  }

  protected function mocks() {
    // stub
  }
}