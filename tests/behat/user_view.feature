@core @core_grades @gradereport_submission
Feature: View the submission report as the student will see it
  In order to know what grades students will see
  As a teacher
  I need to be able to view the user report as that other user

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | t1 |
      | student1 | Student   | 1        | student1@example.com | s1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | idnumber | name                | intro                      | grade | assignsubmission_onlinetext_enabled |
      | assign   | C1     | a1       | Test assignment one | Test assginment submission | 100   | 1                                   |
    When I am on the "a1" "assign activity" page logged in as student1
    Then I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I should see "Submitted for grading"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I change window size to "large"
    And I give the grade "80.00" to the user "Student 1" for the grade item "Test assignment one"
    And I press "Save changes"
    And I change window size to "medium"

  Scenario: View the submission report as the student from both the teachers and students perspective
    When I navigate to "View > Grade and Submissions report" in the course gradebook
    And I select "Student 1" from the "Select all or one user" singleselect
    And I select "User" from the "View report as" singleselect
    Then the following should exist in the "user-grade" table:
      | Grade item          | Grade  | Attempt number | Submission status     |
      | Test assignment one | 80.00  | 1              | Submitted for grading |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grade and Submissions report" in the course gradebook
    Then the following should exist in the "user-grade" table:
      | Grade item          | Grade  | Attempt number | Submission status     |
      | Test assignment one | 80.00  | 1              | Submitted for grading |
