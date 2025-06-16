@search @p1
Feature: Search API

  As a site user, I want to search for content.

  @api
  Scenario: User searches for Page content
    Given page content:
      | title              | body                           | status |
      | [TEST] Test page 1 | test content uniquestring      | 1      |
      | [TEST] Test page 2 | test content otheruniquestring | 1      |
    And I add the "page" content with the title "[TEST] Test page 1" to the search index
    And I add the "page" content with the title "[TEST] Test page 2" to the search index
    And I run search indexing for 2 items
    And I visit "/search"
    And I wait for 5 seconds
    And save screenshot

    When I fill in "search_api_fulltext" with "[TEST]"
    And I press "Apply"
    Then I should see "[TEST] Test page 1" in the ".view-content" element
    And I should see "test content uniquestring" in the ".view-content" element
    And I should see "[TEST] Test page 2" in the ".view-content" element
    And I should see "test content otheruniquestring" in the ".view-content" element

    When I fill in "search_api_fulltext" with "otheruniquestring"
    And I press "Apply"
    Then I should not see "[TEST] Test page 1" in the ".view-content" element
    And I should not see "test content uniquestring" in the ".view-content" element
    And I should see "[TEST] Test page 2" in the ".view-content" element
    And I should see "test content otheruniquestring" in the ".view-content" element
