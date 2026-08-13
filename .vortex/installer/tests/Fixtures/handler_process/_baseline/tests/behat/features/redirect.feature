@redirect @p1
Feature: Redirects

  As a site owner
  I want to ensure that redirects are created and followed
  In order to keep old URLs working after content is moved

  @api
  Scenario: Redirects are created with an explicit and a default status code
    Given the following redirects exist:
      | from            | to          | status_code |
      | /old-login      | /user/login | 301         |
      | /legacy/sign-in | /user/login |             |
    Then the following redirects should exist:
      | from            | to          | status_code |
      | /old-login      | /user/login | 301         |
      | /legacy/sign-in | /user/login | 301         |

  @api
  Scenario: A created redirect sends the visitor to the destination
    Given the following redirects exist:
      | from       | to          | status_code |
      | /old-login | /user/login | 301         |
    And I am an anonymous user
    When I go to "/old-login"
    Then the path should be "/user/login"

  @api
  Scenario: A deleted redirect no longer exists
    Given the following redirects exist:
      | from        | to          |
      | /temp-login | /user/login |
    And the following redirects do not exist:
      | /temp-login |
    Then the following redirects should not exist:
      | /temp-login |
