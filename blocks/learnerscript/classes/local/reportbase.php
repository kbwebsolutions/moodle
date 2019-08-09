<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** LearnerScript
 * A Moodle block for creating LearnerScript
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
namespace block_learnerscript\local;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/evalmath/evalmath.class.php');
require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/component.class.php');
use stdclass;
use block_learnerscript\form\filter_form;
use html_table;
use EvalMath;
use EvalWise;
use component_columns;
use block_learnerscript\local\ls;
use block_learnerscript\locla\querylib;

class reportbase {
    var $id = 0;
    var $components = array();
    var $finalreport;
    var $finalelements;
    var $totalrecords = 0;
    var $currentuser = 0;
    var $currentcourse = 0;
    var $starttime = 0;
    var $endtime = 0;
    var $sql = '';
    var $designpage = true;
    public $tablehead;
    public $ordercolumn;
    public $sqlorder;
    public $exports = true;
    public $start = 0;
    public $length = 10;
    public $search;
    public $courseid;
    public $categoryid;
    public $filteruserid;
    public $cmid;
    public $userid;
    public $status;
    public $filters;
    public $ls_startdate;
    public $ls_enddate;
    public $columns;
    public $basicparams;
    public $params;
    public $filterdata;
    public $role;
    public $parent = true;
    public $courselevel = false;
    public $conditionsenabled = false;
    public $reporttype = 'table';
    public $scheduling = false;
    public $colformat = false;
    public $calculations = false;
    public $preview = false;
    function reports_base($report) {
        global $DB, $CFG, $USER;
        if (empty($report)) {
             return false;
        }
        if (is_numeric($report)) {
            $report_type = $DB->get_field('block_learnerscript', 'type', array('id'=>$report));
        } else {
            $report_type = $report->type;
        }

        require_once($CFG->dirroot . '/blocks/learnerscript/reports/'.$report_type.'/report.class.php');

        if (is_numeric($report)){
            $this->config = $DB->get_record('block_learnerscript', array('id' => $report));
        } else {
            $this->config = $report;
        }
        $reportclassname = 'report_' . $report_type;
        $userid = isset($this->config->userid) && $this->config->userid > 0 ?
                        $this->config->userid : $USER->id;
        $this->userid = $userid;
        $this->courseid = $this->config->courseid;

        $this->categoryid = isset($this->categoryid) && $this->categoryid > 0 ? $this->categoryid : 0; 
        $this->filteruserid = isset($this->filteruserid) && $this->filteruserid > 0 ? $this->filteruserid : 0; 
        if ($USER->id == $userid) {
            $this->currentuser = $USER;
        } else {
            $this->currentuser = $DB->get_record('user', array('id' => $userid));
        }
        $this->role = $this->role ? $this->role : (isset($_SESSION['role']) && $_SESSION['role'] ? $_SESSION['role'] : '');
        $this->currentcourseid = $this->courseid > SITEID ? $this->courseid : $this->config->courseid;
        $this->ls_startdate = 0;
        $this->ls_enddate = time();
        $this->init();
    }
    function init(){
    }
    function __construct($report) {
        $this->courseid = SITEID;
        $this->reports_base($report);
    }

    function check_permissions($userid, $context) {
        global $DB, $CFG, $USER;

        if (has_capability('block/learnerscript:manageownreports', $context, $userid)
            && $this->config->ownerid == $userid){
            return true;
        }

        if (has_capability('block/learnerscript:managereports', $context, $userid)){
            return true;
        }

        if (empty($this->config->visible)){
            return false;
        }

        $components = (new ls)->cr_unserialize($this->config->components);
        $permissions = (isset($components['permissions'])) ? $components['permissions'] : array();

        if (empty($permissions['elements'])) {
            return has_capability('block/learnerscript:viewreports', $context, $userid);
        } else {
            $i = 1;
            $cond = array();
            foreach ($permissions['elements'] as $p) {
                require_once($CFG->dirroot . '/blocks/learnerscript/components/permissions/' .
                    $p['pluginname'] . '/plugin.class.php');
                $classname = 'plugin_' . $p['pluginname'];
                $class = new $classname($this->config);
                $class->role = $this->role;
                $class->userroles = isset($this->userroles) ? $this->userroles : '';
                $cond[$i] = $class->execute($userid, $context, $p['formdata']);
                $i++;
            }
            if (count($cond) == 1) {
                return $cond[1];
            } else {
                $m = new EvalMath;
                $orig = $dest = array();
                if (isset($permissions['config']) && isset($permissions['config']->conditionexpr)) {
                    $logic = trim($permissions['config']->conditionexpr);
                    // Security
                    // No more than: conditions * 10 chars
                    $logic = substr($logic, 0, count($permissions['elements']) * 10);
                    $logic = str_replace(array('and', 'or'), array('&&', '||'), strtolower($logic));
                    // More Security Only allowed chars
                    $logic = preg_replace('/[^&c\d\s|()]/i', '', $logic);
                    //$logic = str_replace('c','$c',$logic);
                    $logic = str_replace(array('&&', '||'), array('*', '+'), $logic);

                    for ($j = $i - 1; $j > 0; $j--) {
                        $orig[] = 'c' . $j;
                        $dest[] = ($cond[$j]) ? 1 : 0;
                    }
                    return $m->evaluate(str_replace($orig, $dest, $logic));
                } else {
                    return false;
                }
            }
        }
    }

    function add_filter_elements(&$mform) {
        global $DB, $CFG;
        $ls = new ls;
        $components = $ls->cr_unserialize($this->config->components);
        $filters = (isset($components['filters'])) ? $components['filters'] : array();
        if(!empty($filters['elements'])) {
            foreach ($filters['elements'] as $f) {
                if ($f['formdata']->value) {
                    require_once($CFG->dirroot . '/blocks/learnerscript/components/filters/' .
                        $f['pluginname'] . '/plugin.class.php');
                    $classname = 'plugin_' . $f['pluginname'];
                    $class = new $classname($this->config);
                    $class->singleselection = true;
                    $this->finalelements = $class->print_filter($mform);
                }
            }
        }
    }
    function initial_basicparams($pluginname) {
        global $CFG;
         require_once($CFG->dirroot . '/blocks/learnerscript/components/filters/' .
            $pluginname . '/plugin.class.php');
        $classname = 'plugin_' . $pluginname;
        $class = new $classname($this->config);
        $class->singleselection = false;
        $selectoption = true;
        $filterarray = $class->filter_data($selectoption);
        $this->filterdata = $filterarray;
    }

    function add_basicparams_elements(&$mform) {
        global $DB, $CFG;
        $ls = new ls;
        $components = $ls->cr_unserialize($this->config->components);
        $basicparams = (isset($this->basicparams)) ? $this->basicparams : array();
        if (!empty($basicparams)) {
            foreach ($basicparams  as $f) {
                if ($f['name'] == 'status') {
                    if($this->config->type == 'useractivities'){
                        $statuslist = array('all' => 'Select Status',
                                            'notcompleted'=>'Not Completed',
                                            'completed'=> 'Completed');
                    } else if ($this->config->type == 'coursesoverview') {
                        $statuslist = array('all' => 'Select Status',
                                            'inprogress'=>'In Progress',
                                            'completed'=> 'Completed');
                    } else {
                       $statuslist = array('all' => 'Select Status',
                                            'inprogress' => 'In Progress',
                                            'notyetstarted' => 'Not Yet Started',
                                            'completed' => 'Completed');
                    }
                    $this->finalelements = $mform->addElement('select', 'filter_status', '',
                        $statuslist, array('data-select2'=>true));
                }else{
                    require_once($CFG->dirroot . '/blocks/learnerscript/components/filters/' .
                        $f['name'] . '/plugin.class.php');
                    $classname = 'plugin_' . $f['name'];
                    $class = new $classname($this->config);
                    $class->singleselection = isset($f['singleselection']) ? $f['singleselection'] : true;
                    $class->placeholder = isset($f['placeholder']) ? $f['placeholder'] : true;
                    $class->maxlength = isset($f['maxlength']) ? $f['maxlength'] : 0;
                    $class->required = true;
                    $this->finalelements = $class->print_filter($mform);
                }
            }
        }

    }

    var $filterform = null;

    function check_filters_request($action = null) {
        global $DB, $CFG;

        $components = (new ls)->cr_unserialize($this->config->components);

        $filters = (isset($components['filters'])) ? $components['filters'] : array();

        if (!empty($filters['elements'])) {

            $formdata = new stdclass;
            $request = array_merge($_POST, $_GET);
            if ($request) {
                foreach ($request as $key => $val) {
                    if (strpos($key, 'filter_') !== false) {
                        $formdata->{$key} = $val;
                    }
                }
            }
            $this->instanceid = $this->config->id;

            $filterform = new filter_form($action, $this);

            $filterform->set_data($formdata);
            if ($filterform->is_cancelled()) {
                if ($action) {
                    redirect($action);
                } else {
                    redirect("$CFG->wwwroot/blocks/learnerscript/viewreport.php?id=" .
                        $this->config->id . "&courseid=" . $this->config->courseid);
                }
                die;
            }
            $this->filterform = $filterform;
        }
    }

    function print_filters($return = false) {
        if (!is_null($this->filterform) && !$return) {
            $this->filterform->display();
        } else if (!is_null($this->filterform)) {
            return $this->filterform->render();
        }
    }
    function evaluate_conditions($data, $logic) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/learnerscript/reports/evalwise.class.php');
        $logic = trim(strtolower($logic));
        $logic = substr($logic, 0, count($data) * 10);
        $logic = str_replace(array('or', 'and', 'not'), array('+', '*', '-'), $logic);
        $logic = preg_replace('/[^\*c\d\s\+\-()]/i', '', $logic);

        $orig = $dest = array();
        for ($j = count($data); $j > 0; $j--) {
            $orig[] = 'c' . $j;
            $dest[] = $j;
        }
        $logic = str_replace($orig, $dest, $logic);
        $m = new EvalWise();
        $m->set_data($data);
        $result = $m->evaluate($logic);
        return $result;
    }

    function get_graphs($finalreport) {
        global $DB, $CFG;
        $components = cr_unserialize($this->config->components);
        $graphs = (isset($components['plot']['elements'])) ? $components['plot']['elements'] : array();
        $reportgraphs = array();
        if (!empty($graphs)) {
            $series = array();
            foreach ($graphs as $g) {
                if ($g['pluginname'] !== 'bar') {
                    require_once($CFG->dirroot . '/blocks/learnerscript/components/plot/' . $g['pluginname'] . '/plugin.class.php');
                    $classname = 'plugin_' . $g['pluginname'];
                    $class = new $classname($this->config);
                    $reportgraphs[] = $class->execute($g['id'], $g['formdata'], $finalreport);
                }
            }
        }
        return $reportgraphs;
    }

    function get_calcs($finaltable, $tablehead) {
        global $DB, $CFG;
        $components = (new ls)->cr_unserialize($this->config->components);
        $calcs = (isset($components['calculations']['elements'])) ? $components['calculations']['elements'] : array();
        // Calcs doesn't work with multi-rows so far
        $columnscalcs = array();
        $calcstype = array();
        $calcsdatatype = array();
        $finalcalcs = array();
        if (!empty($calcs)) {
            foreach ($calcs as $calc) {
                $calc = (array) $calc;
                $calc['formdata'] = (object)$calc['formdata'];
                $calckey = $calc['formdata']->column;
                $columnscalcs[$calckey] = array();
                $calcstype[$calckey] = $calc['formdata']->columname;
                $calcsdatatype[$calc['id']] = $calc['pluginname'];
            }

            $columnstostore = array_keys($columnscalcs);
            foreach ($finaltable as $r) {
                foreach ($columnstostore as $c) {
                    if (isset($r[$c]))
                        $columnscalcs[$c][] = strip_tags($r[$c]);
                }
            }
            foreach ($calcs as $calc) {
                $calc = (array) $calc;
                $calc['formdata'] = $calc['formdata'];
                require_once($CFG->dirroot . '/blocks/learnerscript/components/calcs/' . $calc['pluginname'] . '/plugin.class.php');
                $classname = 'plugin_' . $calc['pluginname'];
                $class = new $classname($this->config);
                $calckey = urldecode($calc['formdata']->column);
                $class->columnname = $calckey;
                $result = $class->execute($columnscalcs[$calckey]);
                $datakey = $calckey.'-'.$calc['pluginname'];

                $finalcalcs[$datakey] = $result;
            }
        }
        $calcsclass = new stdClass();
        $calcsclass->head = $calcstype;
        $calcsclass->data = $finalcalcs;
        $calcsclass->calcdata = $calcsdatatype;
        return $calcsclass;
    }

    function elements_by_conditions($conditions) {
        global $DB, $CFG;
        if (empty($conditions['elements'])) {
            $finalelements = $this->get_all_elements();
            return $finalelements;
        }
        $finalelements = array();
        $i = 1;
        foreach ($conditions['elements'] as $c) {
            require_once($CFG->dirroot.'/blocks/learnerscript/components/conditions/'.$c['pluginname'].'/plugin.class.php');
            $classname = 'plugin_'.$c['pluginname'];
            $class = new $classname($this->config);
            $elements[$i] = $class->execute($c['formdata'], $this->currentuser, $this->currentcourseid);
            $i++;
        }
        if (count($conditions['elements']) == 1) {
            $finalelements = $elements[1];
        } else {
            $logic = $conditions['config']->conditionexpr;
            $finalelements = $this->evaluate_conditions($elements, $logic);
            if ($finalelements === false) {
                return false;
            }
        }
        return $finalelements;
    }


    public function create_report($blockinstanceid = null){
        global $DB, $CFG;
        $components = (new ls)->cr_unserialize($this->config->components);
        $conditions = (isset($components['conditions']['elements']))? $components['conditions']['elements'] : array();
        $filters = (isset($components['filters']['elements']))? $components['filters']['elements'] : array();
        $columns = (isset($components['columns']['elements']))? $components['columns']['elements'] : array();
        $ordering = (isset($components['ordering']['elements']))? $components['ordering']['elements'] : array();
        $columnnames  = array();
        if ($this->preview && empty($columns)) {
            $columns = $this->preview_data();
        }
        foreach ($columns as $key=>$column) {
            if (isset($column['formdata']->column)) {
                $columnnames[$column['formdata']->column] = $column['formdata']->columname;
                $this->selectedcolumns[] = $column['formdata']->column;
            }
        }
        $finalelements = array();
        $sqlorder = '';
        $orderingdata = array();

        if ($this->ordercolumn) {
            $this->sqlorder = $this->selectedcolumns[$this->ordercolumn['column']] . " " .
                            $this->ordercolumn['dir'];
        }else if (!empty($ordering)) {
            foreach ($ordering as $o) {
                require_once($CFG->dirroot.'/blocks/learnerscript/components/ordering/' .
                    $o['pluginname'] . '/plugin.class.php');
                $classname = 'plugin_'.$o['pluginname'];
                $classorder = new $classname($this->config);
                if ($classorder->sql) {
                    $orderingdata = $o['formdata'];
                    $sqlorder = $classorder->execute($orderingdata);
                }
            }
        }
        $conditionfinalelements = array();
        if (!empty($conditions)) {
            $this->conditionsenabled = true;
            $conditionfinalelements = $this->elements_by_conditions($components['conditions']);
        }

        list($finalelements, $this->totalrecords) = $this->get_all_elements($sqlorder, $conditionfinalelements);
        $rows = $this->get_rows($finalelements);
        $reporttable = array();
        $tablehead = array();
        $tablealign =array();
        $tablesize = array();
        $tablewrap = array();
        $firstrow = true;
        $pluginscache = array();

        if ($this->config->type == "topic_wise_performance") {
            $columns = (new ls)->learnerscript_sections_dynamic_columns($columns, $this->config,
                $this->params);
        }

        if ($rows) {
            foreach ($rows as $r) {
                $tempcols = array();
                foreach ($columns as $c) {
                    $c = (array) $c;
                    if (empty($c)) {
                        continue;
                    }
                    require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/' . $c['pluginname'] . '/plugin.class.php');
                    $classname = 'plugin_' . $c['pluginname'];

                    if (!isset($pluginscache[$classname])) {
                        $class = new $classname($this->config, $c);
                        $pluginscache[$classname] = $class;
                    } else {
                        $class = $pluginscache[$classname];
                    }
                    $class->role = $this->role;
                    $class->colformat = $this->colformat;
                    $class->reportinstance = $blockinstanceid ? $blockinstanceid : $this->config->id;
                    if (isset($c['formdata']->column)) {
                        if ($this->reporttype == 'table' || $class->type == 'advanced') {
                            if (!empty($this->params['filter_users'])) {
                                $userrecord = $DB->get_record('user', array('id' => $this->params['filter_users']));
                                $this->currentuser = $userrecord;
                            }
                            $tempcols[$c['formdata']->column] = $class->execute($c['formdata'],
                                                                                $r,
                                                                                    $this->currentuser,
                                                                                    $this->currentcourseid,
                                                                                    $this->starttime,
                                                                                    $this->endtime);
                        } else {
                            $tempcols[$c['formdata']->column] = $r->{$c['formdata']->column};
                        }
                    }

                    if($firstrow){
                        if(isset($c['formdata']->column)) {
                            $columnheading = !empty($c['formdata']->columname) ? $c['formdata']->columname : $c['formdata']->column;
                            $tablehead[$c['formdata']->column] = $columnheading;
                            // $tablehead[$c['formdata']->column] = $c['formdata']->columname;
                        }
                        list($align,$size,$wrap) = $class->colformat($c['formdata']);
                        $tablealign[] = $align;
                        $tablesize[] = $size ? $size . '%' : '';
                        $tablewrap[] = $wrap;
                    }

                }
                $firstrow = false;
                $reporttable[] = $tempcols;
            }
        }

        // EXPAND ROWS
        $finaltable = array();
        $newcols = array();
        foreach($reporttable as $row){
            $col = array();
            $multiple = false;
            $nrows = 0;
            $mrowsi = array();

            foreach($row as $key=>$cell){
                if(!is_array($cell)){
                    $col[$key] = $cell;
                }
                else{
                    $multiple = true;
                    $nrows = count($cell);
                    $mrowsi[] = $key;
                }
            }
            if($multiple){
                $newrows = array();
                for($i = 0; $i < $nrows; $i++){
                    $newrows[$i] = $row;
                    foreach($mrowsi as $index){
                        $newrows[$i][$index] = $row[$index][$i];
                    }
                }
                foreach($newrows as $r)
                    $finaltable[] = $r;
            }
            else{
                $finaltable[] = $col;
            }
        }
        // CALCS
       $finalheadcalcs = $this->get_calcs($finaltable, $tablehead);
       $finalcalcs = $finalheadcalcs->data;

        if ($blockinstanceid == null)
            $blockinstanceid = $this->config->id;

        // Make the table, head, columns, etc...

        $table = new stdClass;
        $table->id = 'reporttable_' . $blockinstanceid . '';
        $table->data = $finaltable;
        $table->head = $tablehead;
        $table->size = $tablesize;
        $table->align = $tablealign;
        $table->wrap = $tablewrap;
        $table->width = (isset($components['columns']['config']))? $components['columns']['config']->tablewidth : '';
        $table->summary = $this->config->summary;
        $table->tablealign = (isset($components['columns']['config']))? $components['columns']['config']->tablealign : 'center';
        $table->cellpadding = (isset($components['columns']['config']))? $components['columns']['config']->cellpadding : '5';
        $table->cellspacing = (isset($components['columns']['config']))? $components['columns']['config']->cellspacing : '1';
        $table->class = (isset($components['columns']['config']))? $components['columns']['config']->class : 'generaltable';

        $calcs = new html_table();
        $calcshead = array();
        // $filterheads = array();
        $calcshead[] = 'Column Name';

        foreach ($finalheadcalcs->calcdata as $key=>$head) {
                $calcshead[$head] = ucfirst(get_string($head, 'block_learnerscript'));
                $calcshead1[$head] = $key;
        }
        $calcsdata = array();
        foreach ($finalheadcalcs->head as $key => $head) {
            $row =array();
            $row [] = $columnnames[$key];
            foreach ($calcshead1 as  $key1=>$value){
                if(array_key_exists($key.'-'.$key1,$finalcalcs)){
                    $row [] = $finalcalcs[$key.'-'.$key1];
                } else{
                    $row [] = 'N/A';
                }
            }
            $calcsdata [] = $row;
        }

        $calcs->data = $calcsdata;
        $calcs->head = $calcshead;
        $calcs->size = $tablesize;
        $calcs->align = $tablealign;
        $calcs->wrap = $tablewrap;
        $calcs->summary = $this->config->summary;
        $calcs->attributes['class'] = (isset($components['columns']['config']))? $components['columns']['config']->class : 'generaltable';

        if(!$this->finalreport) {
            $this->finalreport = new stdClass;
        }
        $this->finalreport->table = $table;
        $this->finalreport->calcs = $calcs;
        return true;
    }
    public function utf8_strrev($str) {
        preg_match_all('/./us', $str, $ar);
        return join('', array_reverse($ar[0]));
    }
    public function preview_data() {
        global $CFG, $DB;
        $allcolumns = $this->columns;
        $columns = array();
        $componentcolumns = get_list_of_plugins('blocks/learnerscript/components/columns');
        foreach ($allcolumns as $key => $c) {
            if (in_array($key, array_values($componentcolumns))) {
                require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/' . $key . '/plugin.class.php');
                $pluginclassname = 'plugin_' . $key;
                $pluginclass = new $pluginclassname(null);

                if ($pluginclass->type == 'advanced') {
                    require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/' . $key . '/form.php');
                    $pluginclassformname = $key . '_form';
                    $compclass = new component_columns($this->config->id);
                    $pluginclassform = new $pluginclassformname(null, array('compclass' => $compclass));
                    $previewcolumns = $pluginclassform->advanced_columns();
                    foreach ($previewcolumns as $preview => $previewcolumn) {
                        $data = array();
                        $data['id'] = random_string(15);
                        $data['pluginname'] = $key;
                        $data['pluginfullname'] = get_string($key, 'block_learnerscript');
                        $data['summary'] = '';
                        $data['type'] = 'selectedcolumns';
                        $list = new stdClass;
                        $list->value = 0;
                        $list->columname = $previewcolumn;
                        $list->column = $preview;
                        $list->heading = $key;
                        $data['formdata'] = $list;
                        $columns[] = $data;
                    }
                } else {
                    foreach ($c as $value) {
                        $data = array();
                        $data['id'] = random_string(15);
                        $data['pluginname'] = $key;
                        $data['pluginfullname'] = get_string($key, 'block_learnerscript');
                        $data['summary'] = '';
                        $data['type'] = 'selectedcolumns';
                        $list = new stdClass;
                        $list->value = 0;
                        $list->columname = $value;
                        $list->column = $value;
                        $list->heading = $key;
                        $data['formdata'] = $list;
                        $columns[] = $data;
                    }
                }
            }
        }
        return $columns;
    }
}