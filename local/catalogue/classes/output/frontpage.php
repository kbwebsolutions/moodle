<?php
// Standard GPL and phpdocs
namespace local_catalogue\output;                                                                                                         
 
use renderable;                                                                                                                     
use renderer_base;                                                                                                                  
use templatable;                                                                                                                    
use stdClass;                                                                                                                       

require_once($CFG->dirroot.'/local/catalogue/lib.php');

class frontpage implements renderable, templatable {                                                                               
    
    /** 
     * @var string
     */
    public $course;
    
 
    public function __construct($courses) {   
        $rendinfo = array();
        
        foreach($courses as $c) {
            $rendinfo[$c->category]->name = $c->fullname;
            $rendinfo[$c->category]->summary = $c->summary;
        }
        //print_r($rendinfo);
        $this->$course = 'English 101';                                                                                                
    }
 
    /**                                                                                                                             
     * Export this data so it can be used as the context for a mustache template.                                                   
     *                                                                                                                              
     * @return stdClass                                                                                                             
     */                                                                                                                             
    public function export_for_template(renderer_base $output) {                                                                    
        $data = new stdClass();                                                                                                     
        $data->sometext = $this->$course;
        $data->othertext = "pengiuns";
        //$data->sometext = $this->$course;                                                                                          
        return $data;                                                                                                               
    }
}