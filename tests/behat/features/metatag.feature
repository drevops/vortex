@metatag @p1
Feature: Metatag

  As a site owner
  I want to ensure that meta tags are rendered on the site pages
  In order to give search engines and social networks accurate page details

  Scenario: Homepage renders the front page meta tags
    Given I am an anonymous user
    When I go to the homepage
    Then the element "link" with the attribute "rel" and the value "canonical" should exist
    And the element "link" with the attribute "rel" and the value "shortlink" should exist
