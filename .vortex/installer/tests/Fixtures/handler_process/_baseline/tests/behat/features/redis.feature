@redis
Feature: Redis cache functionality

  As a site administrator
  I want to verify that Redis caching is working properly
  So that I can ensure optimal site performance and caching functionality

  @api
  Scenario: Redis is working properly
    Given I am logged in as a user with the "administrator" role
    When I go to "/admin/reports/redis"
    Then the response status code should be 200
    And I should see "Connected, using the PhpRedis client"
    And I should not see "0 tags with 0 invalidations"
    And I save screenshot
