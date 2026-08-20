Feature: Email

  As a site administrator
  I want outgoing messages to be captured instead of delivered during tests
  So that I can assert on their recipients and content

  @api @email
  Scenario: Password reset message is captured with its original recipient
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
