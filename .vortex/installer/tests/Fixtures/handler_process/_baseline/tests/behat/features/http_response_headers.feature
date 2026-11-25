@http_response_headers @p1
Feature: HTTP response headers

  As a site owner
  I want every page to restrict browser features and cross-origin access
  So that other sites cannot use the pages against their visitors

  Scenario: Pages carry the cross-origin and browser feature headers
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response header "Permissions-Policy" should contain "camera=(), microphone=(), geolocation=()"
    And the response header "Cross-Origin-Opener-Policy" should contain "same-origin"
    And the response header "Cross-Origin-Resource-Policy" should contain "same-origin"
    And the response header "X-Permitted-Cross-Domain-Policies" should contain "none"

  Scenario: Pages do not name the platform or allow reads from any origin
    Given I am an anonymous user
    When I go to the homepage
    Then the response status code should be 200
    And the response should not contain the header "X-Generator"
    And the response should not contain the header "Access-Control-Allow-Origin"
