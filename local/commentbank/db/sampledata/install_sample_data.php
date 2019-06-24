<?php 

define('CLI_SCRIPT', true);
require_once(__DIR__.'/../../../../config.php');

$table = 'local_commentbank';

global $DB; /** @var moodle_database $DB */
$csv = file("mdl_{$table}.csv");
$headers = array_shift($csv);

foreach ($csv as $row) {
  list($commenttext, $instance,$context, $authoredby,$timemodified,$timecreated,$updatedby) = explode(',', $row);
  $row = (object) [
      'commenttext' => $commenttext,
      'context' =>$context,
       'instance' => $instance,
      'authoredby' => $authoredby,
      'timemodified' => $timemodified,
      'timecreated' => $timecreated,
      'updatedby' =>$updatedby
  ];
  $DB->insert_record($table,$row);
}

echo "Imported mdl_$table.\n";