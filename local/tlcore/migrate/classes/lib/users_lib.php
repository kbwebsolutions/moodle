<?php

namespace local_tlcore\lib;

use context_system;

class users_lib extends base_lib {
  /**
   * Gets all capabilities (booleans) for a given user.  Defaults to current user.
   * @param  int|null $user_id User ID to get caps for
   * @param  context|null $context Optional context, defaults to system.
   * @return array [string => bool]
   */
  public function get_capabilities($user_id, $context = null) {
    global $USER;
    $user_id = $user_id ?: $USER->id;

    $capabilities = [];
    $context = $context instanceof context ? $context : context_system::instance();

    foreach ($context->get_capabilities() as $capability) {
      $capabilities[$capability->name] = has_capability($capability->name, $context, $user_id);
    }

    return $capabilities;
  }
}