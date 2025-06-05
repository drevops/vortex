@homepage @smoke
Feature: Homepage

  Ensure that homepage is displayed as expected.

  @api
  Scenario: Anonymous user visits homepage
    Given I go to the homepage
    And the path should be "<front>"
    Then I save screenshot

  @api @javascript
  Scenario: Anonymous user visits homepage using a real browser
    Given I go to the homepage
    And the path should be "<front>"
    Then I save screenshot
