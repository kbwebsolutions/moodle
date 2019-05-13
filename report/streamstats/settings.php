<?php


defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportstreamstats', get_string('pluginname', 'report_streamstats'), "$CFG->wwwroot/report/streamstats/index.php"));

// no report settings
$settings = null;
