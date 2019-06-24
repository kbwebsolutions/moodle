<?php

namespace local_tlcore\page;

use context_system;

class ajax_page extends base_page {
  public function __construct() {
    global $PAGE;
    $context = context_system::instance();
    $PAGE->set_context($context);
  }

  public function render() {
    $this->content = json_encode($this->content);
    return parent::render();
  }
}