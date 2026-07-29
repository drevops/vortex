@xmlsitemap @p1
Feature: XML Sitemap

  As a site owner
  I want to ensure that the XML sitemap is accessible and correctly configured
  In order to help search engines discover and index my site content

  @api @drush
  Scenario: Verify sitemap.xml exists and is accessible
    Given I run drush "xmlsitemap:regenerate"
    And I am an anonymous user
    When I go to "/sitemap.xml"
    Then the response status code should be 200
    And the response should contain "urlset"
