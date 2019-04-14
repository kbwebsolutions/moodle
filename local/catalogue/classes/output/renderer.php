<?php

namespace local_catalogue\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    /**
     * Defer to template
     * 
     * @param view_page $page
     * 
     * @return string html for the page
     */
    public function render_fontpage($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_catalogue/frontpage', $data);
    }
}