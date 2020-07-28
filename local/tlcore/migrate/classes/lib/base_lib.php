<?php

namespace local_tlcore\lib;

abstract class base_lib {
  private static $instances = [];

  public static function instance() {
    $class = get_called_class();
    if (!isset(self::$instances[$class])) {
      $instance = new $class();
      $instance->init();
      self::$instances[$class] = $instance;
    }
    return self::$instances[$class];
  }

  // don't allow instantiation from outside this class
  private final function __construct() {}

  // don't allow cloning from outside this class
  private final function __clone() {}

  protected function init() {
    // stub
  }
}