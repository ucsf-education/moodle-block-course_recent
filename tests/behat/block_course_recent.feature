@block @block_course_recent @javascript
Feature: The Recent Courses block allows users to easily find and navigate to their most recently accessed courses
  In order to quickly visit the most recently accessed courses
  As a user
  I can use the Recent Courses block in my dashboard

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
      | Course 3 | C3        | 0        |
      | Course 4 | C4        | 0        |
      | Course 5 | C5        | 0        |
      | Course 6 | C6        | 0        |
      | Course 7 | C7        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student1 | C2     | student |
      | student1 | C3     | student |
      | student1 | C4     | student |
      | student1 | C5     | student |
      | student1 | C6     | student |
      | student1 | C7     | student |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference | pagetypepattern | defaultregion |
      | course_recent | System       | 1         | my-index        | content       |

  @accessibility
  Scenario: User has not accessed any course
    Given I log in as "student1"
    Then I should see "You will be able to see recent courses listed here once you have accessed some of your enrolled courses." in the "Recent Courses" "block"
    And I should see "User settings" in the "Recent Courses" "block"
    And the "Recent Courses" "block" should meet accessibility standards with "best-practice" extra tests

  @accessibility
  Scenario: User has accessed two courses
    Given I log in as "student1"
    And I should not see "Course 1" in the "Recent Courses" "block"
    And I should not see "Course 2" in the "Recent Courses" "block"
    And I should see "User settings" in the "Recent Courses" "block"
    When I am on "Course 1" course homepage
    And I am on "Course 2" course homepage
    And I follow "Dashboard"
    Then I should see "Course 1" in the "Recent Courses" "block"
    And I should see "Course 2" in the "Recent Courses" "block"
    And I should not see "Course 3" in the "Recent Courses" "block"
    And I should not see "Course 4" in the "Recent Courses" "block"
    And I should not see "Course 5" in the "Recent Courses" "block"
    And the "Recent Courses" "block" should meet accessibility standards with "best-practice" extra tests

  Scenario: By default, the five most recently accessed courses are shown
    Given I log in as "student1"
    And I should not see "Course 1" in the "Recent Courses" "block"
    And I should not see "Course 2" in the "Recent Courses" "block"
    And I should not see "Course 3" in the "Recent Courses" "block"
    And I should not see "Course 4" in the "Recent Courses" "block"
    And I should not see "Course 5" in the "Recent Courses" "block"
    And I should not see "Course 6" in the "Recent Courses" "block"
    And I should not see "Course 7" in the "Recent Courses" "block"
    When I am on "Course 1" course homepage
    And I am on "Course 2" course homepage
    And I am on "Course 3" course homepage
    And I am on "Course 4" course homepage
    And I am on "Course 5" course homepage
    And I follow "Dashboard"
    Then I should see "Course 1" in the "Recent Courses" "block"
    And I should see "Course 2" in the "Recent Courses" "block"
    And I should see "Course 3" in the "Recent Courses" "block"
    And I should see "Course 4" in the "Recent Courses" "block"
    And I should see "Course 5" in the "Recent Courses" "block"
    And I should not see "Course 6" in the "Recent Courses" "block"
    And I should not see "Course 7" in the "Recent Courses" "block"
    When I am on "Course 6" course homepage
    And I am on "Course 7" course homepage
    And I follow "Dashboard"
    Then I should not see "Course 1" in the "Recent Courses" "block"
    And I should not see "Course 2" in the "Recent Courses" "block"
    And I should see "Course 3" in the "Recent Courses" "block"
    And I should see "Course 4" in the "Recent Courses" "block"
    And I should see "Course 5" in the "Recent Courses" "block"
    And I should see "Course 6" in the "Recent Courses" "block"
    And I should see "Course 7" in the "Recent Courses" "block"

  Scenario: Change maximum list length in user settings
    Given I log in as "student1"
    And I should not see "Course 1" in the "Recent Courses" "block"
    And I should not see "Course 2" in the "Recent Courses" "block"
    And I should not see "Course 3" in the "Recent Courses" "block"
    And I should not see "Course 4" in the "Recent Courses" "block"
    And I should not see "Course 5" in the "Recent Courses" "block"
    And I should not see "Course 6" in the "Recent Courses" "block"
    And I should not see "Course 7" in the "Recent Courses" "block"
    When I am on "Course 1" course homepage
    And I am on "Course 2" course homepage
    And I am on "Course 3" course homepage
    And I am on "Course 4" course homepage
    And I am on "Course 5" course homepage
    And I am on "Course 6" course homepage
    And I am on "Course 7" course homepage
    And I follow "Dashboard"
    Then I should not see "Course 1" in the "Recent Courses" "block"
    And I should not see "Course 2" in the "Recent Courses" "block"
    And I should see "Course 3" in the "Recent Courses" "block"
    And I should see "Course 4" in the "Recent Courses" "block"
    And I should see "Course 5" in the "Recent Courses" "block"
    And I should see "Course 6" in the "Recent Courses" "block"
    And I should see "Course 7" in the "Recent Courses" "block"
    When I follow "User settings"
    Then the field "Maximum number of courses to display" matches value "5"
    When I set the field "Maximum number of courses to display" to "3"
    And I press "Save changes"
    Then I should not see "Course 1" in the "Recent Courses" "block"
    And I should not see "Course 2" in the "Recent Courses" "block"
    And I should not see "Course 3" in the "Recent Courses" "block"
    And I should not see "Course 4" in the "Recent Courses" "block"
    And I should see "Course 5" in the "Recent Courses" "block"
    And I should see "Course 6" in the "Recent Courses" "block"
    And I should see "Course 7" in the "Recent Courses" "block"
