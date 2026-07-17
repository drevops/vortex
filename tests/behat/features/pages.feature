@pages @demo @p1
Feature: Pages listing

  As a site visitor
  I want to see a list of pages
  So that I can browse published content

  @api @testmode
  Scenario: Pages view shows only test content when test mode is enabled
    Given the following page content:
      | title                   | status |
      | [TEST] First test page  | 1      |
      | [TEST] Second test page | 1      |
    When I visit "/pages"
    Then I should see "[TEST] First test page"
    And I should see "[TEST] Second test page"
    And I should not see "Demo page"
