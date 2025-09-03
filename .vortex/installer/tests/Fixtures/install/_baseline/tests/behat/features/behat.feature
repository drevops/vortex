@smoke
Feature: Behat configuration

  As a site administrator
  I want to ensure Behat is properly configured
  So that behavioral tests can run successfully across all environments

  @api
  Scenario: Screenshot functionality works
    Given I am an anonymous user
    When I am on the homepage
    Then I save screenshot
    And save screenshot with name "behat-test-screenshot"

  @api
  Scenario: Drush integration works
    Given I run drush "status"
    Then drush output should contain "Drupal version"
    When I run drush "core:status --field=bootstrap"
    Then drush output should contain "Successful"

  @api
  Scenario: Region map configured correctly
    Given I run drush "pm:enable help"
    And I am logged in as a user with the "administrator" role
    And I go to "/admin/structure/block"
    When I click "Demonstrate block regions"
    Then I should see the ".demo-block" element in the "header" region
    And I should see the ".demo-block" element in the "primary_menu" region
    And I should see the ".demo-block" element in the "secondary_menu" region
    And I should see the ".demo-block" element in the "hero" region
    And I should see the ".demo-block" element in the "highlighted" region
    And I should see the ".demo-block" element in the "breadcrumb" region
    And I should see the ".demo-block" element in the "social" region
    And I should see the ".demo-block" element in the "content_above" region
    And I should see the ".demo-block" element in the "content" region
    And I should see the ".demo-block" element in the "sidebar" region
    And I should see the ".demo-block" element in the "content_below" region
    And I should see the ".demo-block" element in the "footer_top" region
    And I should see the ".demo-block" element in the "footer_bottom" region

  @api
  Scenario: Messages and login selectors configured correctly
    Given users:
      | name | mail             | roles         |
      | test | test@example.com | administrator |
    And I am an anonymous user

    When I go to "/user/login"
    And I fill in "Username" with "test"
    And I fill in "Password" with "test"
    And I press "Log in"
    # Errors content vary between Drupal versions, but all contain "sername" without the leading "u".
    Then I should see the message containing "sername"
    And I should see the error message containing "sername"

    When I am logged in as a user with the "administrator" role
    And I go to "/admin/reports/status"
    And I click "Run cron"
    Then I should see the success message containing "Cron ran successfully"
