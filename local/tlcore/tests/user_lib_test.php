<?php

use local_tlcore\lib\user_lib;
use moodle_database;

class user_lib_text extends advanced_testcase {
    public function test_create_api_user() {
        $this->resetAfterTest();

        global $CFG, $DB; /** @var moodle_database $DB */
        
        // create a user, enabling web services
        $user = user_lib::create_api_user();
        $this->assertEquals('api', $user->username);
        $this->assertTrue(isset($user->id));
        $this->assertNotEmpty($user->id);

        // webservices should be on
        $this->assertTrue( (bool)$CFG->enablewebservices );
        $this->assertEquals('rest', $CFG->webserviceprotocols);

        // webservices role should exist
        $role = $DB->get_record('role', ['shortname' => 'webservices']);
        $this->assertNotEmpty($role);

        // user should be member of role
        $this->assertTrue(user_has_role_assignment($user->id, $role->id));

        
    }
}