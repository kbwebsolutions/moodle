<?php

namespace local_tlcore\page;

abstract class base_page {
  protected $content = "";
  protected $page;

  public function __construct() {
    global $DB, $PAGE;
    $this->page = $PAGE;
    $this->init();
  }

  public function render() {
    global $OUTPUT;
    echo $OUTPUT->header();
    echo $this->content;
    echo $OUTPUT->footer();
  }

  public function set_content($content) {
    $this->content = $content;
    return $this;
  }

  public function set_url($url, array $params = []) {
    $this->page->set_url($url, $params);
    return $this;
  }

  public function set_title($title) {
    $this->page->set_title($title);
    $this->page->set_heading($title);
    return $this;
  }

  public function set_layout($layout) {
    $this->page->set_pagelayout($layout);
    return $this;
  }

  public function set_context($context) {
    $this->page->set_context($context);
    return $this;
  }

  protected function init() {

  }
}