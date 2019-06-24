<?php

namespace local_commentbank\lib;


defined('MOODLE_INTERNAL') || die();


class comment_lib {
    /**
     * Add a new comment to the local_commentbank for re-use in passfail rubric grading form
     * (and potentially other places)
     *
     * @param string $comment
     * @param integer $context
     * @param integer $userid
     * @param integer $instanceid
     * @return boolean
     */
    public static function add_comment(string $comment, int $context, int $userid,int $instanceid) :int {
        global $DB;

        $record = (object) [
                'commenttext'  => $comment,
                'contextlevel' => $context,
                'instanceid'   => ($instanceid ?: 0),
                'authoredby'   => $userid,
                'typemodified' => time(),
                'timecreated'  => time()
        ];
        return $DB->insert_record('local_commentbank', $record);
    }

    public static function update_comment(int $rowid, string $commenttext, int $contextlevel, int $userid,int $instanceid) {
        global $DB;
        $record = (object) [
                'id' => $rowid,
                'commenttext'   => $commenttext,
                'contextlevel'  => $contextlevel,
                'instanceid'    => $instanceid,
                'authoredby'    => $userid,
                'typemodified'  => time(),
                'timecreated'   => time()
        ];
        return $DB->update_record('local_commentbank', $record);
    }
    public static function lookup_context(int $contextid){
        $contexts = [
            CONTEXT_SYSTEM => 'System',
            CONTEXT_COURSECAT => 'Course Category',
            CONTEXT_COURSE => 'Course'
        ];
        if(array_key_exists($contextid, $contexts)){
            return $contexts[$contextid];
        }else{
            return '';
        }
    }
    /**
     * Cannot think of a scenario for this function at the moment
     *
     * @param int $id
     * @return void
     */
    public static function get_comment($id) {
        global $DB;
       $comment = $DB->get_record('local_commentbank', ['id' => $id]); 
       if($comment->contextlevel == CONTEXT_SYSTEM){
           $comment->instance='';
        } else if ($comment->contextlevel == CONTEXT_COURSECAT) { 
           $coursecat = $DB->get_record('course_categories',['id'=>$comment->instanceid]) ;     
           $comment->instance = $coursecat->name;
        } elseif ($comment->contextlevel == CONTEXT_COURSE) {
           $course = $DB->get_record('course',['id'=>$comment->instanceid]) ;     
           $comment->instance = $course->fullname;  
       }
       return $comment;    
/**
 * select * from mdl_local_commentbank cb 
join mdl_context ctx on cb.instanceid = ctx.instanceid and cb.contextlevel = ctx.contextlevel
 */
    }

    /**
    * Get comments for this module. This will return all comments at
    * System level, all for the course category the module is in and
    * all for this specific course.
    *
    * @return array (of objects)
    */
    public static function get_module_comments($courseid) : array {
        global $DB;
        $coursecat = $DB->get_record('course', array('id' => $courseid), 'category')->category;
        $sql = 'select id,commenttext from {local_commentbank} where contextlevel = :context_system
                 or (contextlevel = :context_coursecat AND instanceid = :category)
                 or (contextlevel = :context_course AND instanceid = :courseid)';
                $params= [
                    'context_system'=>CONTEXT_SYSTEM,
                    'context_coursecat'=>CONTEXT_COURSECAT,
                    'context_course'=>CONTEXT_COURSE,
                    'category'=>$coursecat, 
                    'courseid'=>$courseid
                ];
                $comments = $DB->get_records_sql($sql,$params);
                return $comments;
    }
    public static function delete_comment($id) {
        global $DB;
        return $DB->delete_records('local_commentbank', ['id' => $id]);
    }
}