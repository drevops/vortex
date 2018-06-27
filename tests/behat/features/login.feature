Feature: Login

  Ensure that user can login.

  @api @p0
  Scenario: Administrator user logs in
    Given I am logged in as a user with the "administrator" role
    And I am in the "admin" path
    Then I save screenshot

  @api @javascript @p1
  Scenario: Administrator user logs in
    Given I am logged in as a user with the "administrator" role
    And I am in the "admin" path
    Then I save screenshot
