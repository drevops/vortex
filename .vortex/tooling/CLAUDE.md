# Vortex Tooling Package Development Guide

This document describes the Vortex tooling package - a collection of PHP helper functions and scripts for Vortex notification and utility operations.

## Package Overview

**Location**: `.vortex/tooling/`

**Purpose**: Provides reusable PHP functions for Vortex scripts, including:
- Environment variable loading
- Output formatting (info, task, pass, fail, note)
- Variable and command validation
- HTTP requests
- Token replacement utilities

**Key Principle**: This is a **standalone PHP package** that can be used by Vortex scripts without requiring Drupal or Composer dependencies at runtime.

## Core Helper Functions

### Environment Management

```php
load_dotenv(array $env_files = ['.env']): void
```
Loads environment variables from .env files. Supports quoted values and comments.

### Output Formatters

```php
note(string $format, ...$args): void      // Plain note output
task(string $format, ...$args): void      // [TASK] Blue output
info(string $format, ...$args): void      // [INFO] Cyan output
pass(string $format, ...$args): void      // [ OK ] Green output
fail_no_exit(string $format, ...$args): void  // [FAIL] Red output (no exit)
fail(string $format, ...$args): void      // [FAIL] Red output + exit(1)
```

### Validation Functions

```php
validate_variable(string $name, ?string $message = NULL): void
validate_command(string $command): void
```

## Script Structure Guidelines

All scripts in `src/` must follow a consistent structure for maintainability and clarity:

### Standard Script Structure

```php
#!/usr/bin/env php
<?php

/**
 * @file
 * Brief description of what the script does.
 *
 * Additional details about the script's purpose or requirements.
 *
 * IMPORTANT! This script runs outside the container on the host system.
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

require_once __DIR__ . '/helpers.php';

execute_override(basename(__FILE__));

// -----------------------------------------------------------------------------

// Variable description.
//
// Additional details about format, usage, or valid values.
$var1 = getenv_required('PRIMARY_VAR', 'FALLBACK_VAR');

// Another variable description.
//
// Can be 'value1', 'value2', etc.
$var2 = getenv_default('VAR_NAME', 'default_value');

// Optional variable with detailed format explanation.
//
// Format: key1=value1,key2=value2
// Example: web=myorg/myapp,db=myorg/mydb
$var3 = getenv_default('OPTIONAL_VAR', '');

// -----------------------------------------------------------------------------

info('Started operation.');

// Main script logic here...

pass('Finished operation.');
```

### Environment Variable Best Practices

1. **Use VARIABLES Section**: All environment variable declarations at the top
2. **Document Each Variable**: Include comment explaining purpose and format
3. **Use Fallback Chains**: `getenv_required()` and `getenv_default()` with multiple variable names
4. **Separate Sections**: Use comment dividers to separate VARIABLES from EXECUTION
5. **Explicit Validation**: Check for explicitly empty values when needed:

```php
// Check if variable is explicitly set to empty (different from not set).
if (($check = getenv('VAR_NAME')) !== FALSE && empty(trim($check))) {
  fail('VAR_NAME should not be empty.');
  quit(1);
}
```

### Variable Documentation Format

Each variable should have:
- **Single-line comment**: Brief description
- **Multi-line comment** (optional): Format details, examples, valid values
- **Variable assignment**: Using getenv_required() or getenv_default()

**Example**:

```php
// Email notification recipients.
//
// Multiple names can be specified as a comma-separated list of email addresses
// with optional names in the format "email|name".
// Example: "to1@example.com|Jane Doe, to2@example.com|John Doe".
$email_recipients = getenv_required('VORTEX_NOTIFY_EMAIL_RECIPIENTS');

// Email notification subject template.
//
// Available tokens:
// - %project% - Project name
// - %label% - Deployment label
// - %timestamp% - Deployment timestamp
$email_subject = getenv_default('VORTEX_NOTIFY_EMAIL_SUBJECT', '%project% deployment notification');
```

### Real-World Example

See `src/notify-email` for a complete example following this structure.

## Testing Architecture

### Test Organization

The package uses **three types of tests**:

1. **Unit Tests** (`tests/Unit/`) - Test individual helper functions
2. **Self Tests** (`tests/Self/`) - Test the mock infrastructure itself
3. **Fixture Scripts** (`tests/Fixtures/`) - External scripts for integration testing

### Test Development Best Practices

**Environment Variable Management**:
- Use `$this->envSet('VAR', 'value')` for setting single variables
- Use `$this->envSetMultiple(['VAR1' => 'value1', 'VAR2' => 'value2'])` for multiple variables
- Use `$this->envUnset('VAR')` for unsetting single variables
- Use `$this->envUnsetMultiple(['VAR1', 'VAR2'])` for unsetting multiple variables
- Use `$this->envUnsetPrefix('PREFIX_')` for unsetting all variables with a prefix
- NEVER use `putenv()` directly - always use EnvTrait methods for automatic cleanup

**Data Providers - Use Them Extensively**:
- **ALWAYS use data providers** to reduce test duplication when testing the same logic with different inputs
- Use `#[DataProvider('dataProviderMethodName')]` attribute on test methods
- Data provider method names must start with `dataProvider` prefix (e.g., `dataProviderHttpMethods`, not `providerHttpMethods`)
- Data provider methods must be `public static` and return an array

**Advanced Data Provider Patterns**:

When tests need setup or additional assertions, use closures in data provider arrays:

```php
public static function dataProviderWithSetupAndAssertions(): array {
  return [
    'scenario name' => [
      'input' => 'test value',
      'expected' => 'expected value',
      'before' => function(self $test): void {
        // Setup code executed before the test
        $test->envSet('SOME_VAR', 'value');
        $test->mockShellExec('output');
      },
      'after' => function(self $test, $result): void {
        // Additional assertions executed after the test
        $test->assertStringContainsString('expected', $result);
        $test->assertFileExists('/tmp/test.txt');
      },
    ],
  ];
}

#[DataProvider('dataProviderWithSetupAndAssertions')]
public function testWithSetupAndAssertions(
  string $input,
  string $expected,
  ?\Closure $before = NULL,
  ?\Closure $after = NULL
): void {
  // Execute before closure if provided
  if ($before !== NULL) {
    $before($this);
  }

  // Main test logic
  $result = someFunction($input);
  $this->assertEquals($expected, $result);

  // Execute after closure if provided
  if ($after !== NULL) {
    $after($this, $result);
  }
}
```

### Mock System (MockTrait.php)

The package provides a comprehensive mocking system for testing scripts that use:
- `passthru()` - Shell command execution
- `quit()` / `exit()` - Script termination
- `request_*()` - HTTP requests

#### Mock Architecture

**Key Principle**: Use queue-based mock responses that are consumed sequentially.

Each mock system maintains:
- `$mock[Function]Responses` - Array of queued responses
- `$mock[Function]Index` - Current response index
- `$mock[Function]Checked` - Flag to prevent duplicate teardown checks

**Pattern**:
```php
protected function mockPassthru(array $response): void
protected function mockPassthruMultiple(array $responses): void
protected function mockPassthruAssertAllMocksConsumed(): void

protected function mockQuit(int $code = 0): void

protected function mockCurl(array $response): void
protected function mockCurlMultiple(array $responses): void
protected function mockCurlAssertAllMocksConsumed(): void
```

#### Passthru Mocking

**Response Structure**:
```php
[
  'cmd' => 'echo "hello"',        // Required: Expected command
  'output' => 'command output',   // Optional: Output to echo
  'result_code' => 0,             // Optional: Exit code (default: 0)
  'return' => NULL,               // Optional: Return value (NULL or FALSE)
]
```

**Example**:
```php
$this->mockPassthru([
  'cmd' => 'ls -la',
  'output' => 'file.txt',
  'result_code' => 0,
]);

passthru('ls -la', $exit_code);  // Returns mocked output
```

#### Quit Mocking

**Behavior**: Throws exceptions instead of exiting.

- Exit code 0 → `QuitSuccessException`
- Exit code != 0 → `QuitErrorException`

**Example**:
```php
$this->mockQuit(1);

$this->expectException(QuitErrorException::class);
$this->expectExceptionCode(1);

\DrevOps\VortexTooling\quit(1);  // Throws exception
```

## Test Fixtures

### Fixture Naming Convention

Pattern: `test-[function]-[behavior]`

Examples:
- `test-passthru-passing` - Passthru script expecting success
- `test-passthru-failing` - Passthru script expecting failure
- `test-quit-passing` - Quit script with exit code 0
- `test-quit-failing` - Quit script with exit code 1
- `test-request-get-passing` - GET request expecting success
- `test-request-get-failing` - GET request expecting failure
- `test-request-post-passing` - POST request expecting success
- `test-request-post-failing` - POST request expecting failure
- `test-request-multiple` - Multiple HTTP requests

### Fixture Structure

All fixtures must:
1. Start with shebang: `#!/usr/bin/env php`
2. Use strict types: `declare(strict_types=1);`
3. Use namespace: `namespace DrevOps\VortexTooling;`
4. Load helpers: `require_once __DIR__ . '/../../src/helpers.php';`
5. Print output for test assertions
6. Be executable: `chmod +x tests/Fixtures/test-*`

**Example**:
```php
#!/usr/bin/env php
<?php

/**
 * @file
 * GET request fixture for request_get testing.
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../../src/helpers.php';

echo 'Script will call request_get' . PHP_EOL;

$result = request_get('https://example.com/api');

echo 'Response status: ' . $result['status'] . PHP_EOL;
echo 'Response ok: ' . ($result['ok'] ? 'true' : 'false') . PHP_EOL;

if ($result['ok']) {
  echo 'Response body: ' . $result['body'] . PHP_EOL;
}
```

## Debugging

### Enable Debug Output

```bash
# In tests
TEST_VORTEX_DEBUG=1 ./vendor/bin/phpunit

# In scripts
VORTEX_DEBUG=1 php script.php
```

---

*This documentation should be updated whenever significant changes are made to the tooling package structure, mock system, or testing conventions.*
