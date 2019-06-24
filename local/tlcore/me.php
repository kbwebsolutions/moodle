<?php

use local_tlcore\lib\users_lib;
global $USER;

require_once(__DIR__.'/../../config.php');

if (!$USER || !isset($USER->id) || (int)$USER->id === 0) {
  // probably not logged in
  exit;
}

$capabilities = users_lib::instance()->get_capabilities($USER->id);

$user = [
  'id' => (int)$USER->id,
  'firstname' => $USER->firstname,
  'lastname' => $USER->lastname,
  'capabilities' => $capabilities
];

header('Content-Type: application/javascript; charset=utf-8');
echo 'M.user = '.json_encode($user).';';

exit;