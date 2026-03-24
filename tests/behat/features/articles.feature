@articles @p1
Feature: Articles listing

  As a site visitor
  I want to see a list of articles
  So that I can browse published content

  @api @testmode
  Scenario: Articles view shows only test content when test mode is enabled
    Given article content:
      | title                      | status |
      | [TEST] First test article  | 1      |
      | [TEST] Second test article | 1      |
    When I visit "/articles"
    Then I should see "[TEST] First test article"
    And I should see "[TEST] Second test article"
    And I should not see "Demo article"
