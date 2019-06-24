<?php

defined('MOODLE_INTERNAL') || die();

use local_commentbank\lib\comment_lib;

class comment_lib_test extends advanced_testcase {
    public $course;
    public $course2;
    /**
     * Test insertion of a new comment
     */
    public function test_add_comment() {
        $this->resetAfterTest();
        $comment = 'This is a course comment';
        $userid = 2;
        $result = comment_lib::add_comment($comment, CONTEXT_COURSECAT, $userid, $this->course->id);
        $this->assertGreaterThan(0, $result);
    }

    public function  test_get_module_comments() {
        $this->resetAfterTest();
        /* the setup function creates 3 comments that should be seen by $this->course */
        $comments = comment_lib::get_module_comments($this->course->id);
        $this->assertEquals(count($comments), 3);

        $context = CONTEXT_COURSE;
        $commenttext = 'Course level comment on a different course';
        $userid = 2;
        $category = new stdClass();
        $category->name = 'Test Category';
        $cat = $this->getDataGenerator()->create_category($category);
        $this->course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        comment_lib::add_comment($commenttext, $context, $userid, ($this->course2->id));
        /* Shouldn't see the comment for the other course */
        $comments = comment_lib::get_module_comments($this->course->id);
        $this->assertEquals(count($comments),3);
      
        $context = CONTEXT_COURSECAT;
        $commenttext = 'Course cat comment on different category ';
        comment_lib::add_comment($commenttext, $context, $userid, ($this->course2->category));
        $comments = comment_lib::get_module_comments($this->course->id);
        /* Shouldn see comment in different coursecat*/
        $this->assertEquals(count($comments),3);
    }

    public function test_get_comment() {
        $this->resetAfterTest();
        $records = $DB->get_records('local_commentbank');
        $id = array_pop($records)->id;
        $record = comment_lib::get_comment($id);
        $this->assertEquals('comment1', $record->commenttext);
    }

    public function test_delete_comment() {
        $this->resetAfterTest();
        $comment = 'This is a comment';
        $context = 1;
        $userid = 2;
        $id = comment_lib::add_comment($comment, $context, $userid);
        $result = comment_lib::delete_comment($id);
        $this->assertEquals(true, $result);
    }


    public function test_update_comment() {
        $this->resetAfterTest();
        global $DB;
        $records = $DB->get_records('local_commentbank');
        $id = array_pop($records)->id;
        $commenttext = 'update';
        $context = CONTEXT_COURSE;
        $userid = 2;
        $result = comment_lib::update_comment($id, $commenttext, $context, $userid);
        $this->assertEquals(true, $result);
    }
    public function setup() {
        $this->course = $this->getDataGenerator()->create_course();
        $comment = 'Comment at system level';
        $context = CONTEXT_SYSTEM;
        $userid = 2;
        comment_lib::add_comment($comment, $context, $userid,null);
        $context = CONTEXT_COURSECAT;
        $comment = 'Comment at course category level';
        comment_lib::add_comment($comment, $context, $userid,$this->course->category);
        $context = CONTEXT_COURSE;
        $comment = 'Comment at course level';
        comment_lib::add_comment($comment, $context, $userid, $this->course->id);

    }
}
