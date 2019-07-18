@gradingform @gradingform_passfailrubric
Feature: Pass Fail Rubric advanced grading forms can be created and edited
In order to use and refine btec to grade students
As a teacher
I need to edit previously used btec passfailrubric

@javascript
  Scenario: I can use passfailrubric grading to grade and edit them later updating students grades
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | PFR Test assignment 1 name |
      | Description     | Test assignment description |
      | Type            | Scale                       |
      | Scale           | refer_fail_pass             |
      | Grading method  | Pass Fail Rubric                |
    And I go to "PFR Test assignment 1 name" advanced grading definition page
    And I set the following fields to these values:
      | Name        | Assignment 1 PFR     |
      | Description | PFR test description |

    And I click on "Click to edit criterion" "text"

    And I set the field "passfailrubric[criteria][NEWID1][description]" to "Criteria 1 text"

    And I click on "Add criterion" "button"
    
    And I set the field "passfailrubric[criteria][NEWID2][description]" to "Criteria 2 text"
    
    And I click on "#id_savepassfailrubric" "css_element"
    
    Then I should see "Ready for use"
