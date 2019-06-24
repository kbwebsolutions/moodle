<?php
namespace local_tlcore\datatable;

defined('MOODLE_INTERNAL') || die;

class column{

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $field;

    /**
     * @var bool
     */
    public $sortable;

    /**
     * Table header component.
     * We have to violate moodle coding standards for this variable as it's heading out to JS that way and export
     * functions are a dumb waste of time.
     * @var string
     */
    public $thComp;

    /**
     * Table cell component.
     * We have to violate moodle coding standards for this variable as it's heading out to JS that way and export
     * functions are a dumb waste of time.
     * @var string
     */
    public $tdComp;

    /**
     * @var string
     */
    public $colStyle;

    function __construct($title, $field, $sortable, $thComp = null, $tdComp = null, $colStyle = null) {
        $this->title = $title;
        $this->field = $field;
        $this->sortable = $sortable;
        if (!empty($tdComp)) {
            $this->tdComp = $tdComp;
        }
        if (!empty($thComp)) {
            $this->thComp = $thComp;
        }
        if (!empty($colStyle)) {
            $this->colStyle = $colStyle;
        }
    }
}