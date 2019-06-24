<?php

defined('MOODLE_INTERNAL') || die();

function local_tlcore_before_standard_html_head() {
  global $PAGE; /** @var moodle_page $PAGE */

  // include assets from tlcore
  // $PAGE->requires->css(new moodle_url('/local/tlcore/vendor.css'));
  // $PAGE->requires->css(new moodle_url('/local/tlcore/bundle.css'));
  // $PAGE->requires->js(new moodle_url('/local/tlcore/vendor.js'));
  // $PAGE->requires->js(new moodle_url('/local/tlcore/bundle.js'));
}

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