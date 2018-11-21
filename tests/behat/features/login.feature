@smoke
Feature: Login

  Ensure that user can login.

  @api @p0
  Scenario: Administrator user logs in
    Given I am logged in as a user with the "administer site configuration, access administration pages" permissions
    When I go to "admin"
    Then I save screenshot

  @api @javascript @p1
  Scenario: Administrator user logs in
    Given I am logged in as a user with the "administer site configuration, access administration pages" permissions
    When I go to "admin"
    Then I save screenshot
