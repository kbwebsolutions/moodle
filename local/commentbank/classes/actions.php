<?php

namespace local_commentbank;

defined('MOODLE_INTERNAL') || die();

use local_commentbank\services\commentbank;
use local_tlcore\datatable\query;

class actions {

    public static function get_comments() {
        $query = required_param('query', PARAM_TEXT);
        $cbservice = new commentbank();
        return $cbservice->run_query(new query($query));
    }
}