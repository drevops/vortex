@homepage @smoke
Feature: Homepage

  As a site visitor
  I want to access the homepage
  So that I can view the main landing page and navigate the site

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
