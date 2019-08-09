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
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;

class report_usersscorm extends reportbase {
    /**
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report);
        $this->components = array('columns', 'conditions', 'ordering', 'permissions', 'filters', 'plot');
        $this->parent = false;
        $this->columns = array('userfield' => ['userfield'] , 'usersscormcolumns' => array('inprogress',
            'completed', 'notattempted', 'total', 'lastaccess', 'firstaccess', 'totaltimespent', 'numviews'));
        $this->basicparams = array(['name' => 'coursecategories'], ['name' => 'courses']);
        $this->courselevel = true;
        $this->filters = array('users');
        $this->orderable = array('fullname', 'inprogress', 'completed', 'notattempted', 'totaltimespent', 'numviews', 'firstaccess', 'lastaccess');

    }
    /**
     * @param  string  $sqlorder user order
     * @param  array  $conditionfinalelements userids
     * @return array array($users, $usercount) list and count of users
     */
    public function get_all_elements($sqlorder = '', $conditionfinalelements = array()) {
        global $DB;
        
        if (!isset($this->params['filter_coursecategories']) && $this->params['filter_coursecategories'] > 0) {
            $this->initial_basicparams('coursecategories');
            $filterdata = array_keys($this->filterdata);
            $this->params['filter_coursecategories'] = array_shift($filterdata);   
        }
        if (!isset($this->params['filter_courses']) && $this->params['filter_courses'] > SITEID) {
            $this->initial_basicparams('courses');
            $this->params['filter_courses'] = array_shift(array_keys($this->filterdata));
        }
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        }
        $userid = isset($this->params['filter_users']) ? $this->params['filter_users'] : array();
        $searchconcat = '';
        if (isset($this->search) && $this->search) {
            $fields = array("CONCAT(u.firstname, ' ', u.lastname)", "u.email");
            $fields = implode(" LIKE '%" . $this->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $this->search . "%' ";
            $searchconcat = " AND ($fields) ";
        }
        if (!empty($userid) && $userid != '_qf__force_multiselect_submission') {
            is_array($userid) ? $userid = implode(',', $userid) : $userid;
            $searchconcat .= " AND u.id IN ($userid)";
        }
        $courselogfilter = '';
        $coursetimefilter = '';
	$concatsql = '';
        if ($this->params['filter_courses'] > SITEID) {
            $courseid = $this->params['filter_courses'];
            $coursefilter = $searchconcat .= " AND c.id IN ($courseid)";
            $coursetimefilter = " AND mt.courseid IN ($courseid)";
            $courselogfilter = " AND lsl.courseid IN ($courseid)";
            $concatsql = " AND c.id IN ($courseid)";
            $scormcourseid = " AND s.course IN ($courseid)";
        }
        if (isset($this->params['filter_users']) && $this->params['filter_users'] > SITEID) {
            $filter_users = $this->params['filter_users'];
            $concatsql .= " AND u.id = $filter_users";
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
        if($this->ls_startdate >= 0 && $this->ls_enddate) {
            $searchconcat .= " AND ra.timemodified BETWEEN $this->ls_startdate AND $this->ls_enddate ";
        }
        if ($this->role == 'student') {
            return array(array(), 0);
        }
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $coursesql  = (new querylib)->get_learners('u.id','');
        $countsql  = " SELECT count(DISTINCT u.id) ";
        $selectsql = " SELECT DISTINCT u.id as userid, 'total', CONCAT(u.firstname,' ',u.lastname) as fullname, c.category AS categoryid";
        if (in_array('totaltimespent', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT SUM(mt.timespent) from {block_ls_modtimestats} as mt 
                         JOIN {course_modules} cm ON cm.id = mt.activityid 
                         JOIN {modules} m ON m.id = cm.module WHERE m.name='scorm' AND mt.userid = u.id $coursetimefilter) as totaltimespent";
        }
        if (in_array('numviews', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT COUNT(DISTINCT lsl.userid) 
                        FROM {logstore_standard_log} lsl 
                        JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid
                        JOIN {modules} m ON m.id = cm.module
                        WHERE m.name='scorm' AND lsl.crud = 'r' AND lsl.contextlevel = 70 AND lsl.anonymous = 0 AND lsl.userid = u.id $courselogfilter) AS distinctusers,
                    (SELECT COUNT('X') FROM {logstore_standard_log} lsl JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid
                        JOIN {modules} m ON m.id = cm.module
                        WHERE m.name='scorm' AND  lsl.crud = 'r' AND lsl.contextlevel = 70  AND lsl.anonymous = 0 AND lsl.userid = u.id $courselogfilter) AS numviews";
        }
        if (in_array('totalscorms', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT count(DISTINCT cm.id)
                            FROM {course} AS c
                            JOIN {enrol} AS e ON c.id = e.courseid AND e.status = 0
                            JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.status = 0
                            JOIN {role_assignments} AS ra ON ra.userid = ue.userid AND ra.roleid = 5
                            JOIN {context} AS con ON con.contextlevel = 50 AND c.id = con.instanceid
                            JOIN {course_modules} AS cm ON cm.course = c.id
                            JOIN {modules} AS m ON m.id = cm.module
                            JOIN {scorm} AS s ON s.course = c.id
                            LEFT JOIN {scorm_scoes_track} AS st ON st.scormid = s.id
                            JOIN {scorm_scoes} ss ON ss.scorm = s.id
                            WHERE ue.userid = u.id AND con.id = ra.contextid AND cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1 AND m.name = 'scorm' $searchconcat ) as totalscorms ";
        }

        if (in_array('inprogress', $this->selectedcolumns)) {
            $selectsql .= ",  (SELECT count(DISTINCT s.id)
                                FROM {scorm} as s
                                JOIN {scorm_scoes_track} st ON st.scormid = s.id
                                JOIN {course_modules} AS cm ON cm.instance = s.id AND cm.visible =1 AND cm.deletioninprogress = 0
                                JOIN {role_assignments} AS ra ON st.userid = ra.userid
                                JOIN {role} as r on r.id=ra.roleid AND r.shortname='student'
                                JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.instanceid =cm.course
                                JOIN {course} as c ON c.id = ctx.instanceid  AND c.visible =1
                                JOIN {modules} AS m ON m.id = cm.module AND m.name = 'scorm'
                                WHERE st.userid = u.id
                                AND s.id NOT IN
                                    (SELECT s.id
                                       FROM {scorm} as s
                                       JOIN {course_modules} as cm ON cm.instance = s.id
                                       JOIN {course} as c ON c.id = cm.course
                                       JOIN {modules} as m ON m.id = cm.module
                                       JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate > 0
                                      WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1 AND m.name = 'scorm' AND cmc.userid = u.id AND cm.course IN ($coursesql))
                                AND s.course IN ($coursesql) $searchconcat ) as inprogress ";
        }
        if (in_array('notattempted', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT count(DISTINCT cm.instance)
                            FROM {course} AS c
                            JOIN {enrol} AS e ON c.id = e.courseid
                            JOIN {user_enrolments} AS ue ON ue.enrolid = e.id
                            JOIN {role_assignments} AS ra ON ra.userid = ue.userid AND ra.roleid = 5
                            JOIN {context} AS con ON con.contextlevel = 50 AND c.id = con.instanceid
                            JOIN {course_modules} AS cm ON cm.course = c.id
                            JOIN {modules} AS m ON m.id = cm.module
                            WHERE ue.userid = u.id AND con.id = ra.contextid AND cm.visible = 1 AND cm.deletioninprogress = 0 AND m.name = 'scorm' AND c.visible = 1
                            AND cm.instance NOT IN (SELECT st.scormid FROM {scorm_scoes_track}
                            st WHERE st.userid = u.id AND st.element = 'x.start.time') AND cm.course IN ($coursesql)
                            AND cm.instance NOT IN (SELECT DISTINCT s.id
                                    FROM {scorm} as s
                                    JOIN {course_modules} as cm ON cm.instance = s.id
                                    JOIN {modules} as m ON m.id = cm.module
                                    JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate > 0
                                    JOIN {role_assignments} AS ra ON cmc.userid = ra.userid
                                    JOIN {role} as r on r.id=ra.roleid AND r.shortname='student'
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.instanceid =cm.course
                                    JOIN {course} as c ON c.id = ctx.instanceid
                                    WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1 AND m.name = 'scorm' AND cmc.userid = u.id AND cm.course IN ($coursesql)) $searchconcat ) as notattempted ";
        }
        if (in_array('completed', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT COUNT(DISTINCT s.id)
                            FROM {scorm} as s
                            JOIN {course_modules} as cm ON cm.instance = s.id
                            JOIN {modules} as m ON m.id = cm.module
                            JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate > 0
                            JOIN {role_assignments} AS ra ON cmc.userid = ra.userid
                            JOIN {role} as r on r.id=ra.roleid AND r.shortname='student'
                            JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.instanceid =cm.course
                            JOIN {course} as c ON c.id = ctx.instanceid
                            WHERE cm.visible = 1 AND cm.deletioninprogress = 0 AND c.visible = 1 AND m.name = 'scorm' AND cmc.userid = u.id AND cm.course IN ($coursesql) $coursefilter ) as completed ";
        }

        if (in_array('firstaccess', $this->selectedcolumns)) {
            $selectsql .= ", (SELECT MIN(sst.value) FROM {scorm_scoes_track} sst 
                                JOIN {scorm} s ON s.id = sst.scormid 
                                WHERE sst.userid = u.id $scormcourseid AND sst.element = 'x.start.time') as firstaccess";
        }

        $formsql  .= " FROM {course} AS c
                        JOIN {enrol} AS e ON c.id = e.courseid AND e.status = 0
                        JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.status = 0
                        JOIN {role_assignments} AS ra ON ra.userid = ue.userid
                        JOIN {context} con ON c.id = con.instanceid
                        JOIN {role} AS rl ON rl.id = ra.roleid AND rl.shortname = 'student'
                        JOIN {user} AS u ON u.id = ue.userid
                        WHERE ra.roleid = :roleid AND ra.contextid =con.id
                        AND u.confirmed = 1 AND u.deleted = 0 $concatsql $searchconcat";
        if ((!is_siteadmin() || $this->scheduling) && $this->role != 'manager') {
            $roleshortname = $this->role;
            $mycourses = (new querylib)->get_rolecourses($this->userid, $roleshortname);
            $mycourseids = implode(',', array_keys($mycourses));
            if (!empty($mycourses)) {
                $mycourseids = implode(',', array_keys($mycourses));
                $formsql .= " AND c.id IN ($mycourseids) ";
            } else {
                return array(array(), 0);
            }
        }
        $params['roleid'] = $studentroleid;

        try {
            $usercount = $DB->count_records_sql($countsql . $formsql, $params);
        } catch (dml_exception $e) {
            $usercount = 0;
        }
        try {
            if (!empty($this->sqlorder)) {
                $formsql .=" ORDER BY ". $this->sqlorder;
            } else {
                $formsql .= " ORDER BY u.id DESC ";
            }
            $users = $DB->get_records_sql($selectsql.$formsql, $params, $this->start, $this->length);
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

}
