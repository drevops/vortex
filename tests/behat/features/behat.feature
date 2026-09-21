@behat @smoke
Feature: Behat configuration

  As a site administrator
  I want to ensure Behat is properly configured
  So that behavioral tests can run successfully across all environments

  @api @javascript
  Scenario: Screenshot functionality works
    Given the user is anonymous
    When I go to the homepage
    Then I save screenshot
    And I save screenshot with name "behat-test-screenshot"

  @api @javascript
  Scenario: Animated screenshot is recorded across multiple steps
    Given the user is anonymous
    When I go to the homepage
    And I go to "/user/login"
    Then the path should be "/user/login"

  @api @javascript @breakpoint:mobile_portrait
  Scenario: Viewport is resized from a tag and from a step
    Given the user is anonymous
    When I go to the homepage
    And I set the viewport to the "tablet_landscape" breakpoint
    And I set the viewport to "1920" by "1080"
    And I go to "/user/login"
    Then the path should be "/user/login"

  @api
  Scenario: REST requests are sent and asserted
    Given the REST header "Accept" has the value "text/html"
    When I send a REST "GET" request to "/user/login"
    Then the REST response status code should be 200
    And the REST response should contain "user-login-form"

  @api
  Scenario: XML responses are asserted
    Given the response XML is loaded from the file "response.xml"
    Then the response should be in XML format
    And the XML should use the namespace "https://example.com/meta"
    And the XML element "//items" should have "2" elements
    And the XML element "//item[@id='1']/title" should be equal to "First item"
    And the XML element "//item[2]/title" should contain "Second"
    And the XML attribute "id" on element "//item[2]" should be equal to "2"
    And the XML element "//missing" should not exist

  @api
  Scenario: JSON responses are asserted
    Given the response JSON is loaded from the file "response.json"
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
    Given the page cache for the path "/" is empty
    And the render cache is empty
    And the user is anonymous
    When I go to the homepage
    Then the response status code should be 200

  @api
  Scenario: Drush integration works
    When I run the drush command "status"
    Then the drush output should contain the value "Drupal version"
    When I run the drush command "core:status" with the arguments "--field=bootstrap"
    Then the drush output should contain the value "Successful"

  @api
  Scenario: Region map configured correctly
    When I run the drush command "pm:enable" with the arguments "help"
    And I log in as a user with the "administrator" role
    And I go to "/admin/structure/block"
    And I follow "Demonstrate block regions"
    Then the element ".demo-block" should exist in the region "header"
    And the element ".demo-block" should exist in the region "primary_menu"
    And the element ".demo-block" should exist in the region "secondary_menu"
    And the element ".demo-block" should exist in the region "hero"
    And the element ".demo-block" should exist in the region "highlighted"
    And the element ".demo-block" should exist in the region "breadcrumb"
    And the element ".demo-block" should exist in the region "social"
    And the element ".demo-block" should exist in the region "content_above"
    And the element ".demo-block" should exist in the region "content"
    And the element ".demo-block" should exist in the region "sidebar"
    And the element ".demo-block" should exist in the region "content_below"
    And the element ".demo-block" should exist in the region "footer_top"
    And the element ".demo-block" should exist in the region "footer_bottom"

  @api
  Scenario: Messages and login selectors configured correctly
    Given the following users exist:
      | name | mail             | roles         |
      | test | test@example.com | administrator |
    And the user is anonymous

    When I go to "/user/login"
    And I fill in "Username" with "test"
    And I fill in "Password" with "test"
    And I press "Log in"
    # Errors content vary between Drupal versions, but all contain "sername" without the leading "u".
    Then the message "sername" should exist
    And the error message "sername" should exist

    When I log in as a user with the "administrator" role
    And I go to "/admin/reports/status"
    And I follow "Run cron"
    Then the success message "Cron ran successfully" should exist
