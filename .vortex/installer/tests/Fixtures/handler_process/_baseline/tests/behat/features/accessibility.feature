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

  # Pages other than the homepage are audited in advisory mode (failures are
  # reported but do not fail the build) and are collected into the site-wide
  # accessibility report written after the suite to
  # '.logs/test_results/accessibility/'.
  @api @javascript @accessibility:warning
  Scenario: Secondary pages are audited for the site-wide accessibility report
    Given I am an anonymous user
    When I go to "/user/login"
    And I go to "/user/password"
    Then the path should be "/user/password"
