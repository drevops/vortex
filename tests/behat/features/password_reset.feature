Feature: Password reset

  As a site visitor who has forgotten their password
  I want to be sent a one-time login link
  So that I can get back into my account without contacting an administrator

  @api @email
  Scenario: Visitor requests a one-time login link
    Given the following users:
      | name          | mail                      |
      | test_password | test_password@example.com |
    And I am an anonymous user

    When I go to "/user/password"
    And I fill in "Username or email address" with "test_password@example.com"
    And I press "Submit"

    Then an email should be sent to the "test_password@example.com"
    And an email should be sent to the address "test_password@example.com" with the content containing:
      """
      user/reset
      """
