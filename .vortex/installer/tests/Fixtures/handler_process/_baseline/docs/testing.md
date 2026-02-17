# Testing

This document describes **what** testing conventions and agreements apply to
this project.

## PHPUnit conventions

Unit, Kernel, Functional tests.

See [documentation](https://www.vortextemplate.com/docs/development/phpunit)
on how to run tests, configure environment variables and code coverage, and use
test reports in continuous integration pipeline.

### Test class structure

All PHPUnit tests must follow this structure:

```php
<?php

namespace Drupal\Tests\my_module\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests for MyClass.
 *
 * @group my_module
 */
class MyClassTest extends UnitTestCase {

  /**
   * Test that something works.
   */
  public function testSomethingWorks(): void {
    // Prepare.
    $input = 'test';

    // Act.
    $result = my_function($input);

    // Assert.
    $this->assertEquals('expected', $result);
  }

}
```

### Base test classes

- **Unit** - `Drupal\Tests\UnitTestCase` - Testing isolated PHP classes
- **Kernel** - `Drupal\KernelTests\KernelTestBase` - Testing with database/services
- **Functional** - `Drupal\Tests\BrowserTestBase` - Testing with full browser
- **FunctionalJavascript** - `Drupal\FunctionalJavascriptTests\WebDriverTestBase` - Testing browser interaction with JavaScript

### Test data conventions

- **Always prefix test content**: `[TEST] Node Title`
- **Use data providers**: For testing multiple input/output combinations
  - Must be a public static method
  - Must follow naming convention `dataProvider<MethodName>`
  - Must be placed after the test method it provides data for.
- **Use unique identifiers**: Include test class or method name in test data

### Data providers

Always use data providers for testing multiple input/output combinations:

```php
/**
 * Test my function with various inputs.
 *
 * @dataProvider dataProviderMyFunction
 */
public function testMyFunction(string $input, string $expected): void {
  $this->assertEquals($expected, my_function($input));
}

/**
 * Data provider for testMyFunction.
 */
public static function dataProviderMyFunction(): array {
  return [
    'empty string' => ['', ''],
    'simple string' => ['hello', 'HELLO'],
    'with numbers' => ['test123', 'TEST123'],
  ];
}
```

### Testing services (Kernel tests)

For Kernel tests that need Drupal services:

```php
<?php

namespace Drupal\Tests\my_module\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for MyService.
 *
 * @group my_module
 */
class MyServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['my_module', 'node', 'user'];

  /**
   * The service under test.
   */
  protected $myService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->myService = $this->container->get('my_module.my_service');
  }

  /**
   * Test service method.
   */
  public function testServiceMethod(): void {
    $result = $this->myService->doSomething();
    $this->assertNotNull($result);
  }

}
```

## Behat conventions

BDD end-to-end tests.

See [documentation](https://www.vortextemplate.com/docs/development/behat)
on how to run Behat tests, configure environment variables, and use test reports
in continuous integration pipeline.

### User story format

All Behat features must follow this format:

```gherkin
Feature: [Feature name]

  As a [user type]
  I want to [action/goal]
  So that [benefit/outcome]
```

### Standard user types

Use these consistent user type descriptions:

```gherkin
As a site visitor          # Anonymous users
As a site administrator    # Admin users
As a content editor        # Content management users
As a authenticated user    # Logged-in users
```

### Test data conventions

- **Always prefix test content**: `[TEST] Page Title`
- **Use numbered patterns**: `[TEST] Topic 1`, `[TEST] Topic 2`
- **Avoid real names**: Don't use "Workshop" or "Training"
- **Be descriptive**: `[TEST] Event with All Fields`

### Example feature file

```gherkin
Feature: Homepage

  As a site visitor
  I want to access the homepage
  So that I can view the main landing page and navigate the site

  Scenario: View homepage content
    Given I am on the homepage
    Then I should see "[TEST] Welcome Message"
    And I should see "About Us" in the "navigation" region
```

### Content type testing process

When creating comprehensive tests for content types:

1. Analyze configuration first

   - Check `config/default/field.field.node.[type].*.yml`
   - Review `core.entity_view_display.node.[type].default.yml`
   - Identify visible vs hidden fields

2. Create supporting entities

  ```gherkin
  Background:
    Given "tags" terms:
      | name              |
      | [TEST] Topic 1    |
      | [TEST] Topic 2    |

    And the following media "image" exist:
      | name                    |
      | [TEST] Featured Image 1 |
  ```

3. Test all visible fields

  ```gherkin
  Scenario: View complete content with all fields
    Given "page" content:
      | title                     | body                          | field_tags         |
      | [TEST] Complete Page Test | [TEST] This is the body text. | [TEST] Topic 1     |
    When I visit "[TEST] Complete Page Test"
    Then I should see "[TEST] Complete Page Test"
    And I should see "[TEST] This is the body text."
    And I should see "[TEST] Topic 1"
  ```
