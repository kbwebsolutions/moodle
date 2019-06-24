<?php
namespace local_tlcore\datatable;

defined('MOODLE_INTERNAL') || die;

class query{

    /**
     * @var int
     */
    public  $limit;

    /**
     * @var int
     */
    public  $offset;

    /**
     * @var string
     */
    public  $sort;

    /**
     * @var string
     */
    public  $order;

    /**
     * @var string
     */
    public $filter;

    /**
     * Populate the class properties from the json.
     *
     * @param string $json
     */
    function __construct($json) {

        // Set defaults.
        $this->limit = 10;
        $this->offset = 0;
        $this->sort = null;
        $this->order = null;
        $this->filter = null;

        // Cleaning.
        $cleaning = [
            'limit' => PARAM_INT,
            'offset' => PARAM_INT,
            'sort' => PARAM_ALPHANUMEXT,
            'order' => PARAM_ALPHA,
            'filter' => PARAM_TEXT
        ];

        // Load from json.
        $data = json_decode($json);
        foreach($data as $key => $datum){
            // Clean the json value before attributing to class property.
            $cleankey = trim($key);
            $cleankey = strtolower($cleankey);
            if (isset($cleaning[$cleankey])) {
                $datum = clean_param($datum, $cleaning[$cleankey]);
            }
            $this->$key = $datum;
        }
    }
}







