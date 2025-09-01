@search @p1
Feature: Search API

  As a site visitor
  I want to search for content
  So that I can find relevant information quickly

  @api
  Scenario: User searches for Page content
    Given page content:
      | title                              | status | moderation_state |
      | [TEST] Test page uniquestring      | 1      | published        |
      | [TEST] Test page otheruniquestring | 1      | published        |
      | [TEST] Test page thirduniquestring | 0      | draft        |
    And I add the "page" content with the title "[TEST] Test page uniquestring" to the search index
    And I add the "page" content with the title "[TEST] Test page otheruniquestring" to the search index
    And I add the "page" content with the title "[TEST] Test page thirduniquestring" to the search index
    And I run search indexing for 3 items
    And I wait for 5 seconds
    And I visit "/search"
    And save screenshot

    When I fill in "search_api_fulltext" with "[TEST]"
    And I press "Apply"
    Then I should see "[TEST] Test page uniquestring" in the ".view-content" element
    And I should see "[TEST] Test page otheruniquestring" in the ".view-content" element
    And I should not see "[TEST] Test page thirduniquestring" in the ".view-content" element

    When I fill in "search_api_fulltext" with "otheruniquestring"
    And I press "Apply"
    Then I should not see "[TEST] Test page uniquestring" in the ".view-content" element
    And I should see "[TEST] Test page otheruniquestring" in the ".view-content" element
    And I should not see "[TEST] Test page thirduniquestring" in the ".view-content" element
