@a11y
Feature: Accessibility

  As a site visitor
  I want the site to meet accessibility standards
  So that all users can access content regardless of ability

  @api @javascript
  Scenario: Anonymous user visits an accessible homepage
    Given I am an anonymous user
    When I go to the homepage
    Then the current page should pass accessibility checks
