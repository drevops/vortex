@behat @smoke
Feature: Behat configuration

  As a site administrator
  I want to ensure Behat is properly configured
  So that behavioral tests can run successfully across all environments

  @api @javascript
  Scenario: Screenshot functionality works
    Given I am an anonymous user
    When I go to the homepage
    Then I save screenshot
    And I save screenshot with name "behat-test-screenshot"

  @api @javascript
  Scenario: Animated screenshot is recorded across multiple steps
    Given I am an anonymous user
    When I go to the homepage
    And I go to "/user/login"
    Then the path should be "/user/login"

  @api @javascript @breakpoint:mobile_portrait
  Scenario: Viewport is resized from a tag and from a step
    Given I am an anonymous user
    When I go to the homepage
    And I set the viewport to the "tablet_landscape" breakpoint
    And I set the viewport to "1920" by "1080"
    And I go to "/user/login"
    Then the path should be "/user/login"

  @api
  Scenario: REST requests are sent and asserted
    Given a REST header "Accept" with value "text/html"
    When I send a REST "GET" request to "/user/login"
    Then the REST response status code should be 200
    And the REST response should contain "user-login-form"

  @api
  Scenario: XML responses are asserted
    Given the response content from the file "response.xml"
    Then the response should be in XML format
    And the XML should use the namespace "https://example.com/meta"
    And the XML element "//items" should have "2" elements
    And the XML element "//item[@id='1']/title" should be equal to "First item"
    And the XML element "//item[2]/title" should contain "Second"
    And the XML attribute "id" on element "//item[2]" should be equal to "2"
    And the XML element "//missing" should not exist

  @api
  Scenario: JSON responses are asserted
    Given the response JSON from the file "response.json"
    Then the response should be in JSON format
    And the JSON path "$.status" should be equal to "ok"
    And the JSON path "$.items" should have "2" elements
    And the JSON path "$.items[0].title" should be equal to "First item"
    And the JSON path "$.items[*].id" should exist
    And the JSON path "$.missing" should not exist
    And the response should match the following JSON schema:
      """
      {
        "type": "object",
        "required": ["status", "items"]
      }
      """

  @api
  Scenario: Caches are invalidated from within a scenario
    Given the page cache for the path "/" has been cleared
    And the render cache has been cleared
    And I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200

  @api
  Scenario: Drush integration works
    Given I run drush "status"
    Then the drush output should contain "Drupal version"
    When I run drush "core:status --field=bootstrap"
    Then the drush output should contain "Successful"

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
    Given the following users:
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
