@counter
Feature: Counter Block

  As a site visitor
  I want to interact with the counter block
  So that I can increment decrement and reset the counter value

  @api @javascript
  Scenario: Counter block is visible on homepage
    Given I go to the homepage
    Then I should see a ".ys-base-counter-block" element
    And the ".ys-base-counter-label" element should contain "Counter:"
    And the ".ys-base-counter-value" element should contain "0"

    When I click on the element ".ys-base-counter-btn--increment"
    Then the ".ys-base-counter-value" element should contain "1"
    When I click on the element ".ys-base-counter-btn--increment"
    Then the ".ys-base-counter-value" element should contain "2"

    When I click on the element ".ys-base-counter-btn--decrement"
    Then the ".ys-base-counter-value" element should contain "1"
    When I click on the element ".ys-base-counter-btn--decrement"
    Then the ".ys-base-counter-value" element should contain "0"

    When I click on the element ".ys-base-counter-btn--decrement"
    Then the ".ys-base-counter-value" element should contain "-1"

    When I click on the element ".ys-base-counter-btn--reset"
    Then the ".ys-base-counter-value" element should contain "0"

  @api @javascript
  Scenario: Counter persistence across page reloads
    Given I go to the homepage
    When I click on the element ".ys-base-counter-btn--increment"
    And I click on the element ".ys-base-counter-btn--increment"
    And I click on the element ".ys-base-counter-btn--increment"
    Then the ".ys-base-counter-value" element should contain "3"
    When I reload the page
    Then the ".ys-base-counter-value" element should contain "3"
