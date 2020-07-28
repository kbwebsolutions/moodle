<?php

defined('MOODLE_INTERNAL') || die();

if (!function_exists('dd')) {
  
  /**
   * Var dump and die.
   * @return void
   */
  function dd(...$things) {
    var_dump(...$things);
    exit;
  }
}