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

    # The subject and body below are the text Drupal ships for this message.
    # Update them here when this site rewords the password reset email.
    Then an email should be sent to the "test_password@example.com"
    And the email field "subject" should contain:
      """
      Replacement login information for
      """
    And an email should be sent to the address "test_password@example.com" with the content containing:
      """
      A request to reset the password for your account has been made at
      """
    And an email should be sent to the address "test_password@example.com" with the content containing:
      """
      You may now log in by clicking this link or copying and pasting it into your browser:
      """
    And an email should be sent to the address "test_password@example.com" with the content containing:
      """
      user/reset
      """
