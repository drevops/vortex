@behat @smoke
Feature: Behat configuration

  As a site administrator
  I want to ensure Behat is properly configured
  So that behavioral tests can run successfully across all environments

  @javascript
  Scenario: Screenshot functionality works
    Given I am an anonymous user
    When I go to the homepage
    Then I save screenshot
    And I save screenshot with name "behat-test-screenshot"

  @javascript
  Scenario: Animated screenshot is recorded across multiple steps
    Given I am an anonymous user
    When I go to the homepage
    And I go to "/user/login"
    Then the path should be "/user/login"

  @javascript @breakpoint:mobile_portrait
  Scenario: Viewport is resized from a tag and from a step
    Given I am an anonymous user
    When I go to the homepage
    And I set the viewport to the breakpoint "tablet_landscape"
    And I set the viewport to "1920" by "1080"
    And I go to "/user/login"
    Then the path should be "/user/login"

  Scenario: REST requests are sent and asserted
    Given the REST header "Accept" has the value "text/html"
    When I send a REST "GET" request to "/user/login"
    Then the REST response status code should be 200
    And the REST response should contain "user-login-form"

  Scenario: XML responses are asserted
    Given the response XML is loaded from the file "response.xml"
    Then the response should be in XML format
    And the XML should use the namespace "https://example.com/meta"
    And the XML element "//items" should have "2" elements
    And the XML element "//item[@id='1']/title" should have the value "First item"
    And the XML element "//item[2]/title" should contain "Second"
    And the XML attribute "id" on the element "//item[2]" should have the value "2"
    And the XML element "//missing" should not exist

  Scenario: JSON responses are asserted
    Given the response JSON is loaded from the file "response.json"
    Then the response should be in JSON format
    And the JSON path "$.status" should have the value "ok"
    And the JSON path "$.items" should have "2" elements
    And the JSON path "$.items[0].title" should have the value "First item"
    And the JSON path "$.items[*].id" should exist
    And the JSON path "$.missing" should not exist
    And the response should match the following JSON schema:
      """
      {
        "type": "object",
        "required": ["status", "items"]
      }
      """

  Scenario: Caches are invalidated from within a scenario
    Given the page cache for the path "/" has been cleared
    And the render cache has been cleared
    And I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200

  Scenario: Drush integration works
    When I run the drush command "status"
    Then the drush output should contain "Drupal version"
    When I run the drush command "core:status" with the arguments "--field=bootstrap"
    Then the drush output should contain "Successful"

  Scenario: Region map configured correctly
    When I run the drush command "pm:enable" with the arguments "help"
    Given I am logged in as a user with the role "administrator"
    And I go to "/admin/structure/block"
    When I follow "Demonstrate block regions"
    Then the element ".demo-block" in the region "header" should exist
    And the element ".demo-block" in the region "primary_menu" should exist
    And the element ".demo-block" in the region "secondary_menu" should exist
    And the element ".demo-block" in the region "hero" should exist
    And the element ".demo-block" in the region "highlighted" should exist
    And the element ".demo-block" in the region "breadcrumb" should exist
    And the element ".demo-block" in the region "social" should exist
    And the element ".demo-block" in the region "content_above" should exist
    And the element ".demo-block" in the region "content" should exist
    And the element ".demo-block" in the region "sidebar" should exist
    And the element ".demo-block" in the region "content_below" should exist
    And the element ".demo-block" in the region "footer_top" should exist
    And the element ".demo-block" in the region "footer_bottom" should exist

  Scenario: Messages and login selectors configured correctly
    Given the following users exist:
      | name | mail             | roles         |
      | test | test@example.com | administrator |
    And I am an anonymous user

    When I go to "/user/login"
    And I fill in "Username" with "test"
    And I fill in "Password" with "test"
    And I press "Log in"
    # Errors content vary between Drupal versions, but all contain "sername" without the leading "u".
    Then I should see the message "sername"
    And I should see the error message "sername"

    Given I am logged in as a user with the role "administrator"
    When I go to "/admin/reports/status"
    And I follow "Run cron"
    Then I should see the success message "Cron ran successfully"
