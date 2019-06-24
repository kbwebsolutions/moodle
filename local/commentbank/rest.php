<?php

use local_tlcore\output\rest_renderer;
use local_commentbank\actions;

require_once(__DIR__.'/../../config.php');

$output = new rest_renderer();

// call the action on the action plans actions class
$out = $output->call_action(actions::class);

// output the JSON and exit
$output->json(['result' => $out]);
$output->exit();