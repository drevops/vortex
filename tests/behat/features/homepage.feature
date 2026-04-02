@homepage @smoke
Feature: Homepage

  As a site visitor
  I want to access the homepage
  So that I can view the main landing page and navigate the site

  @api
  Scenario: Anonymous user visits homepage
    Given I am an anonymous user
    When I go to the homepage
    Then the path should be "<front>"
    And I save screenshot

  @api @javascript
  Scenario: Anonymous user visits homepage using a real browser
    Given I am an anonymous user
    When I go to the homepage
    Then the path should be "<front>"
    And I save screenshot
