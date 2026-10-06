@login @smoke
Feature: Login

  As a site administrator
  I want to log into the system
  So that I can access administrative functions and manage the site

  Scenario: Administrator user logs in
    When I log in as a user with the permissions "administer site configuration, access administration pages"
    And I go to "admin"
    Then the path should be "/admin"
    And I save screenshot

  @javascript
  Scenario: Administrator user logs in using a real browser
    When I log in as a user with the permissions "administer site configuration, access administration pages"
    And I go to "admin"
    Then the path should be "/admin"
    And I save screenshot
