<?php
// Standard GPL and phpdocs
namespace block_learnerscript\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;
class filtertoggleform implements renderable, templatable {
    public $filterform;
    public function __construct($filterform = false) {
        $this->filterform = $filterform;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $OUTPUT;
        $data = new stdClass();
        $data->filterform = $this->filterform;
        return $data;
    }
}