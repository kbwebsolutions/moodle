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
 * @package    report
 * @subpackage streamstats
 * @copyright  2019 Chartered College of Teaching
 * @author     Kieran Briggs <kbriggs@chartered.college>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class queries {

    public $category;
    public $timeback;
    public $studentrole;

    public function get_category_courses(){
        global $DB;

        $cat = $this->category;

        $sql = 'SELECT id, category
                FROM {course}
                WHERE category = :category';

         $courses = $DB->get_records_sql($sql, ['category' => $cat]);

        return $courses;
    }

    public function get_participant_numbers(){
        global $DB;
        $roleid = $this->studentrole;
        $cat = $this->category;
        $from = $this->timeback;

        $fromdate = time() - ($from * 24 * 60 * 60);

        $sql = "SELECT DISTINCT c.id, c.fullname,
                    (SELECT COUNT(distinct(ra.userid)) AS Users FROM {role_assignments} AS ra JOIN {context} AS ctx ON ra.contextid = ctx.id WHERE ra.roleid = :student AND ctx.instanceid = c.id ) AS Participants,
                    (SELECT count(cc.userid) FROM {course_completions} AS cc WHERE cc.course = c.id AND cc.timecompleted IS NOT NULL) as completions,
                    (SELECT count(distinct(log.userid)) as Active FROM {logstore_standard_log}  AS log JOIN {role_assignments} as ra ON ra.userid = log.userid AND ra.contextid = log.contextid WHERE log.action = 'viewed' AND log.target  = 'course' AND log.timecreated > :fromdate AND log.courseid = c.id AND ra.roleid = :roleid2) AS activeusers,
                    (SELECT count(cmc.id) FROM {course_modules_completion} AS cmc JOIN {course_modules} AS cm ON cm.id = cmc.coursemoduleid WHERE cm.course = c.id AND cm.deletioninprogress = 0 AND cm.visible = 1) as completedactivities               
                FROM {course} as c
                JOIN {enrol} as e on e.courseid = c.id
                WHERE e.roleid = :participant AND c.category = :category
                ORDER BY c.fullname DESC";


        $enrolments = $DB->get_records_sql($sql, ['student' => $roleid, 'participant' => $roleid, 'category' => $cat, 'fromdate' => $fromdate, 'roleid2'=> $roleid]);

        return $enrolments;
    }



    public function get_forum_discussions() {
        global $DB;

        $cat = $this->category;

        $sql = 'SELECT c.fullname as Module,f.name as Forum,
                (SELECT COUNT(id) FROM {forum_discussions} AS fd WHERE f.id = fd.forum) AS "Discussions",
                (SELECT COUNT(fp.id) FROM {forum_discussions} AS fd JOIN {forum_posts} AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS "TotalPosts",
                (SELECT COUNT( ra.userid ) AS Students FROM {role_assignments} AS ra JOIN {context} AS ctx ON ra.contextid = ctx.id WHERE ra.roleid =5 AND ctx.instanceid = c.id ) AS "TotalStudentsInteracted",
                (SELECT COUNT( ra.userid ) AS Users FROM {role_assignments} AS ra JOIN {context} AS ctx ON ra.contextid = ctx.id WHERE ra.roleid IN (3,5) AND ctx.instanceid = c.id ) AS "TotalStudentsCourse"
                FROM {forum} AS f 
                JOIN {course} AS c ON f.course = c.id
                WHERE `type` != "news" AND c.category = :category
                ORDER BY Module DESC';

        $rs = $DB->get_records_sql($sql, ['category' => $cat]);

        return $rs;
    }

    public function get_course_completions() {
        global $DB;

        $sql = 'SELECT cc.course AS MoudleID, c.fullname as Module, count(cc.userid) AS Completed FROM {course_completions} as cc
                JOIN {course} as c ON c.id = cc.course
                WHERE cc.timecompleted IS NOT NULL
                GROUP BY cc.course
                ORDER BY c.fullname';

        $rs = $DB->get_records_sql($sql);

        return $this->category;

    }
}