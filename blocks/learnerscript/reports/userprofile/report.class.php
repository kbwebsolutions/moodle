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

/**
 * LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas Info Solutions
 * @date: 2017
 */
require_once $CFG->libdir . '/coursecatlib.php';
use block_learnerscript\local\reportbase;
use block_learnerscript\local\ls;
use block_learnerscript\local\querylib;

class report_userprofile extends reportbase {
    /**
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        $this->components = array('columns', 'conditions', 'ordering', 'filters', 'plot');
        $this->parent = false;
        $this->basicparams = [['name' => 'coursecategories'], ['name' => 'users', 'singleselection' =>false, 'placeholder' => false, 'maxlength' => 5]];
        $this->columns = array('userfield' => array('userfield'), 'userprofile' => array('enrolled', 'inprogress',
            'completed', 'completedcoursesgrade', 'quizes', 'assignments', 'scorms', 'badges', 'progress', 'status'));
        // $this->filters = array('users');
        $this->exports = false;
        $this->orderable = array();

    }
    /**
     * @param  string  $sqlorder user order
     * @param  array  $conditionfinalelements userids
     * @return array array($users, $usercount) list and count of users
     */
    public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
        global $DB;

        $searchconcat = '';
        $concatsql = '';

        if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        } 
        if($this->role != 'student' && !isset($this->params['filter_users'])){
            $this->initial_basicparams('users');
            $this->params['filter_users'] = array_shift(array_keys($this->filterdata));
        }
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
        $userid = isset($this->params['filter_users']) && $this->params['filter_users'] > 0
                    ? $this->params['filter_users'] : $this->userid;
        if(empty($userid)) {
            return array(array(), 0);
        }
        if(is_array($userid)){
            $userid = implode(',', $userid);
        }
        if (isset($this->search) && $this->search) {
            $fields = array("CONCAT(u.firstname, ' ', u.lastname)", "u.email");
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $searchconcat = " AND ($fields) ";
        }
        $params = array();
        if (!empty($conditionfinalelements)) {
            $conditions = implode(',', $conditionfinalelements);
            $searchconcat .= " AND u.id IN (:conditions)";
            $params['conditions'] = $conditionfinalelements;
        }

       if (!empty($this->params['filter_coursecategories'])) {
            $categoryid = $this->params['filter_coursecategories'];
            $ids = [$categoryid];
            $category = \coursecat::get($categoryid);
            $categoryids = array_merge($ids, $category->get_all_children_ids());
        }
        $catids = implode(',', $categoryids);
        if (!empty($catids)) {
             $concatsql .= " AND c.category IN ($catids) ";
        }
        if(isset($this->params['filter_users']) && $this->params['filter_users'] > 0) {
            $userids = $this->params['filter_users'];
           // $concatsql .= " AND u.id = $userids";
           // $params['filter_users'] = $this->params['filter_users'];
        }

        $enrolleddatesql = '';
        $inprogressdatesql = '';
        $comdatesql = '';
        $quizsql = '';
        $assignsql = '';
        $scormsql = '';
        $gradesql = '';
        $badgesql = '';
        if ($this->ls_startdate >= 0 && $this->ls_enddate) {
            $enrolleddatesql = " AND ra.timemodified BETWEEN :startdate AND :enddate ";
            $inprogressdatesql = " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $comdatesql = " AND cc.timecompleted BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $activitydatesql = " AND cm.added BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $badgedatesql = " AND bi.dateissued BETWEEN $this->ls_startdate AND $this->ls_enddate ";
            $params['startdate'] = $this->ls_startdate;
            $params['enddate'] = $this->ls_enddate;
        }
        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            return array(array(), 0);
        }
        $learnercoursesql  = (new querylib)->get_learners('u.id','');
        $countsql  = " SELECT count(DISTINCT u.id) ";
        $selectsql = " SELECT DISTINCT u.id as userid, CONCAT(u.firstname,' ',u.lastname) AS fullname,  c.category AS categoryid ";

        if(in_array('enrolled', $this->selectedcolumns)){
            $selectsql .= ", COUNT(DISTINCT c.id) AS enrolled";
        }
        if(in_array('inprogress', $this->selectedcolumns)){
            $selectsql .= ", (COUNT(DISTINCT c.id) - COUNT(DISTINCT cc.id)) AS inprogress";
        }
        if(in_array('completed', $this->selectedcolumns)){
            $selectsql .= ", COUNT(DISTINCT cc.id) AS completed";
        }
        if(in_array('progress', $this->selectedcolumns)){
            $selectsql .= ", ROUND(COUNT(cc.id)/COUNT(DISTINCT c.id)*100, 2) AS progress";
        }
        if(in_array('assignments', $this->selectedcolumns)){
            $selectsql .= ", (SELECT count(cm.id)
                                FROM {course_modules} AS cm
                                JOIN {modules} AS m ON m.id = cm.module
                                WHERE m.name = 'assign'
                                AND cm.course IN ($learnercoursesql) AND cm.visible = 1 AND cm.deletioninprogress = 0  $activitydatesql) AS assignments  ";
        }
        if(in_array('quizes', $this->selectedcolumns)){
            $selectsql .= ", (SELECT count(cm.id)
                                FROM {course_modules} AS cm
                                JOIN {modules} AS m ON m.id = cm.module
                                WHERE m.name = 'quiz'
                                AND cm.course IN ($learnercoursesql) AND cm.visible = 1 AND cm.deletioninprogress = 0 $activitydatesql) AS quizes ";
        }
        if(in_array('scorms', $this->selectedcolumns)){
            $selectsql .= ", (SELECT count(cm.id)
                                FROM {course_modules} AS cm
                                JOIN {modules} AS m ON m.id = cm.module
                                WHERE m.name = 'scorm'
                                AND cm.course IN ($learnercoursesql) AND cm.visible = 1 AND cm.deletioninprogress = 0 $activitydatesql) AS scorms ";
        }
        if(in_array('badges', $this->selectedcolumns)){
            $selectsql .= ", (SELECT count(bi.id) FROM {badge_issued} as bi
                                JOIN {badge} as b ON b.id = bi.badgeid
                               WHERE  bi.visible = 1 AND b.status != 0
                                 AND b.status != 2 AND b.status != 4 AND bi.userid = u.id
                                 $badgedatesql) as badges ";
        }
        if(in_array('completedcoursesgrade', $this->selectedcolumns)){
            $selectsql .= ", (SELECT CONCAT(ROUND(sum(gg.finalgrade), 2),' / ', ROUND(sum(gi.grademax), 2))
                               FROM {grade_grades} AS gg
                               JOIN {grade_items} AS gi ON gi.id = gg.itemid
                               JOIN {course_completions} AS cc ON cc.course = gi.courseid
                               JOIN {course} AS c ON cc.course = c.id AND c.visible=1
                              WHERE gi.itemtype = 'course' AND cc.course = gi.courseid
                                AND cc.timecompleted IS NOT NULL AND cc.course IN ($learnercoursesql)
                                AND gg.userid = cc.userid AND cc.userid = u.id $comdatesql) as completedcoursesgrade ";
        }
        $fromsql  .= "FROM {user} u
                      JOIN {role_assignments} ra ON ra.userid = u.id
                      JOIN {context} AS ctx ON ctx.id = ra.contextid
                      JOIN {course} c ON c.id = ctx.instanceid
                      JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
                      JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.userid = ra.userid AND ue.status = 0
                      JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                 LEFT JOIN {course_completions} cc ON cc.course = ctx.instanceid AND cc.userid = u.id AND cc.timecompleted > 0
                     WHERE c.visible = 1 AND u.confirmed = 1 AND u.deleted = 0  AND u.id in($userid) $inprogressdatesql";
        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            $roleshortname = $this->role;
            $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
            $mycourseids = implode(',', array_keys($mycourses));
            if (!empty($mycourses)) {
                $mycourseids = implode(',', array_keys($mycourses));
                $sql .= " AND c.id IN ($mycourseids) ";
            } else {
                return array(array(), 0);
            }
        }
        try {
            $usercount = $DB->count_records_sql($countsql . $fromsql. $searchconcat, $params);
        } catch (dml_exception $e) {
            $usercount = 0;
        }
        try {
            $fromsql .= " GROUP BY u.id";
            if(!empty($this->sqlorder)){
                $fromsql .=" ORDER BY ". $this->sqlorder;
            } else{
                if(!empty($sqlorder)){
                    $fromsql .= " ORDER BY u.$sqlorder";
                } else{
                    $fromsql .= " ORDER BY u.id DESC";
                }
            }

            $users = $DB->get_records_sql($selectsql . $fromsql, $params, $this->start, $this->length);

        } catch (dml_exception $e) {
            $users = array();
        }
        return array($users, $usercount);
    }
    /**
     * @param  array $users users
     * @return array $data users courses information
     */
    public function get_rows($users) {
        return $users;
    }
    function create_report($blockinstanceid = null){
        global $DB, $CFG;
        $components = (new ls)->cr_unserialize($this->config->components);
        $userid = isset($this->params['filter_users']) && $this->params['filter_users'] > 0
            ? $this->params['filter_users'] : $this->userid;


        $conditions = (isset($components['conditions']['elements']))? $components['conditions']['elements'] : array();
        $filters = (isset($components['filters']['elements']))? $components['filters']['elements'] : array();
        $columns = (isset($components['columns']['elements']))? $components['columns']['elements'] : array();
        $ordering = (isset($components['ordering']['elements']))? $components['ordering']['elements'] : array();
        $columnnames  = array();

        foreach ($columns as $key=>$column){
            if(isset($column['formdata']->column)){
                $columnnames[$column['formdata']->column] = $column['formdata']->columname;
                $this->selectedcolumns[] = $column['formdata']->column;
            }
        }
        $finalelements = array();
        $sqlorder = '';
        $orderingdata = array();

        if($this->ordercolumn){
            $this->sqlorder =  $this->selectedcolumns[$this->ordercolumn['column']] . " " . $this->ordercolumn['dir'];
        }else if(!empty($ordering)){
            foreach($ordering as $o){
                require_once($CFG->dirroot.'/blocks/learnerscript/components/ordering/'.$o['pluginname'].'/plugin.class.php');
                $classname = 'plugin_'.$o['pluginname'];
                $classorder = new $classname($this->config);
                if ($classorder->sql) {
                    $orderingdata = $o['formdata'];
                    $this->sqlorder = $classorder->execute($orderingdata);
                }
            }
        }
        $conditionfinalelements = array();
        if(!empty($conditions)){
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

         if($this->config->type == "topic_wise_performance"){
             $columns = (new ls)->learnerscript_sections_dynamic_columns($columns,$this->config,$this->params);
          }

        if($rows){
            $tempcols = array();
            foreach($rows as $r){
                foreach($columns as $c){
                    $c = (array) $c;
                    if (empty($c)) {
                        continue;
                    }
                        require_once($CFG->dirroot . '/blocks/learnerscript/components/columns/' . $c['pluginname'] . '/plugin.class.php');
                        $classname = 'plugin_' . $c['pluginname'];

                        if(!isset($pluginscache[$classname])){
                            $class = new $classname($this->config,$c);
                            $pluginscache[$classname] = $class;
                        }
                        else{
                            $class = $pluginscache[$classname];
                        }
                        $class->role = $this->role;
                        $class->reportinstance = $blockinstanceid ? $blockinstanceid : $this->config->id;
                        if(isset($c['formdata']->column)) {
                            // if(!empty($this->params['filter_users'])){
                            //     $userrecord= $DB->get_record('user', array('id' => $this->params['filter_users']));
                            //     $this->currentuser = $userrecord;
                            // }
                            $tempcols[$c['formdata']->columname][] = $class->execute($c['formdata'], $r,
                                                                                $this->currentuser,
                                                                                $this->currentcourseid,
                                                                                $this->starttime,
                                                                                $this->endtime);
                        }

                    if($firstrow){
                        if(isset($c['formdata']->column)) {
                            $columnheading = !empty($c['formdata']->columname) ? $c['formdata']->columname : $c['formdata']->column;
                            $tablehead[$c['formdata']->columname] = $columnheading;
                            // $tablehead[$c['formdata']->column] = $c['formdata']->columname;
                        }
                        list($align,$size,$wrap) = $class->colformat($c['formdata']);
                        $tablealign[] = $align;
                        $tablesize[] = $size ? $size . '%' : '';
                        $tablewrap[] = $wrap;
                    }

                }
                $firstrow = false;

            }
            $reporttable = $tempcols;
        }
        // EXPAND ROWS
        $finaltable = array();
        $newcols = array();
        $i=0;
        foreach($reporttable as $key=>$row){
            $r = array_values($row);
           $r[] = $key;

           $finaltable[] = array_reverse($r);
                $i++;
        }
        // CALCS
        $finalheadcalcs = $this->get_calcs($finaltable, $tablehead);
        $finalcalcs = $finalheadcalcs->data;

        if ($blockinstanceid == null)
            $blockinstanceid = $this->config->id;

        // Make the table, head, columns, etc...

        $table = new html_table;
        // $table->id = 'repsorttable_' . $blockinstanceid . '';
        $table->data = $finaltable;
        for($i=0 ; $i < (count($userid)+1); $i++){
            $table->head[] = '';
        }
        $table->size = $tablesize;
        $table->align = $tablealign;
        $table->wrap = $tablewrap;
        $table->width = (isset($components['columns']['config']))? $components['columns']['config']->tablewidth : '';
        $table->summary = $this->config->summary;
        $table->tablealign = (isset($components['columns']['config']))? $components['columns']['config']->tablealign : 'center';
        $table->cellpadding = (isset($components['columns']['config']))? $components['columns']['config']->cellpadding : '5';
        $table->cellspacing = (isset($components['columns']['config']))? $components['columns']['config']->cellspacing : '1';

        if(!$this->finalreport) {
            $this->finalreport = new stdClass;
        }
        $this->finalreport->table = $table;
        $this->finalreport->calcs = $calcs;
        return true;
    }
}
