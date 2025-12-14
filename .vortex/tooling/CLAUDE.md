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

## Project Structure

```text
.vortex/tooling/
├── src/
│   └── helpers.php              # Core helper functions
├── tests/
│   ├── Fixtures/                # Test fixture scripts
│   │   ├── test-passthru-*      # Passthru test fixtures
│   │   ├── test-quit-*          # Quit test fixtures
│   │   ├── test-request-*       # HTTP request test fixtures
│   │   └── test-exit-*          # Exit test fixtures
│   ├── Self/                    # Self-tests for mock infrastructure
│   │   ├── MockPassthruSelfTest.php
│   │   ├── MockQuitSelfTest.php
│   │   └── MockRequestSelfTest.php
│   ├── Unit/                    # Unit tests
│   │   ├── UnitTestCase.php     # Base test case
│   │   ├── ExitException.php    # Exit exception for testing
│   │   ├── ExitMockTest.php     # Exit mocking tests
│   │   ├── HelpersDotenvTest.php          # Environment tests
│   │   └── SelfTest.php         # Self-tests for core functions
│   └── Traits/
│       └── MockTrait.php        # Mock infrastructure (passthru, quit, request)
├── composer.json                # Dev dependencies only
├── phpunit.xml                  # PHPUnit configuration
└── CLAUDE.md                    # This file
```

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

### HTTP Request Functions

```php
request_get(string $deploy_webhook_url, array $headers = [], int $timeout = 10): array
request_post(string $deploy_webhook_url, $body = NULL, array $headers = [], int $timeout = 10): array
request(string $deploy_webhook_url, array $options = []): array
```

**Return Format**:
```php
[
  'ok' => bool,           // TRUE if HTTP < 400
  'status' => int,        // HTTP status code
  'body' => string|false, // Response body
  'error' => ?string,     // cURL error message
  'info' => array,        // cURL info array
]
```

### Environment Variable Functions

```php
getenv_required(...$var_names): string
getenv_default(...$args): string
```

**getenv_required()** - Get value from environment variables with fallback chain:
- Tries each variable name in order
- Returns first non-empty value found
- Calls `fail()` and `quit(1)` if no value found
- Example: `getenv_required('VAR1', 'VAR2', 'VAR3')`

**getenv_default()** - Get value with fallback chain and default:
- Last argument is the default value
- Tries each variable name in order
- Returns first non-empty value found, or default if none found
- Example: `getenv_default('VAR1', 'VAR2', 'default_value')`

### Utility Functions

```php
replace_tokens(string $template, array $replacements): string
is_debug(): bool
quit(int $code = 0): void  // Wrapper around exit() for testing
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

**When to Use Before/After Closures**:
- **Before**: When different test cases need different mock setups, environment variables, or file preparations
- **After**: When different test cases need additional scenario-specific assertions beyond the main test logic
- **Avoid**: Don't use closures for simple cases - keep data providers simple when possible

**Real-World Example from Codebase**:

See `tests/Self/MockShellExecSelfTest.php` for simple data provider usage:

```php
#[DataProvider('dataProviderMockShellExec')]
public function testMockShellExec(string|null|false $mock_value): void {
  $this->mockShellExec($mock_value);
  $result = shell_exec('echo "test"');

  if ($mock_value === NULL) {
    $this->assertNull($result);
  }
  elseif ($mock_value === FALSE) {
    $this->assertFalse($result);
  }
  else {
    $this->assertEquals($mock_value, $result);
  }
}

public static function dataProviderMockShellExec(): array {
  return [
    'string output' => ['command output'],
    'null output' => [NULL],
    'false output' => [FALSE],
    'empty string output' => [''],
  ];
}
```

This replaces 4 separate test methods with a single parameterized test.

**Documentation**:
- Block comments (PHPDoc /** ... */) are ONLY allowed on test classes, NOT on methods
- Do NOT add block comments to test methods, data provider methods, or helper methods
- Inline comments (// ...) are acceptable for explaining logic within method bodies
- Keep test method names descriptive enough that block comments aren't needed

**Allowed Comments**:
```php
// ✅ CORRECT - Block comment on class only
/**
 * Tests for webhook notification script.
 */
#[RunTestsInSeparateProcesses]
class NotifyWebhookTest extends UnitTestCase {

  // ✅ CORRECT - No block comment on methods, only inline comments
  public function testSuccessfulNotification(): void {
    // Mock HTTP request
    $this->mockRequest('https://example.com', [], ['status' => 200]);
    // ... test code ...
  }

  // ✅ CORRECT - No block comment on data provider
  public static function dataProviderHttpMethods(): array {
    return ['POST' => ['POST'], 'GET' => ['GET']];
  }
}

// ❌ INCORRECT - Block comments on methods
/**
 * Test successful notification.  // <-- Remove this
 */
public function testSuccessfulNotification(): void {
  // ...
}
```

**Example**:
```php
protected function setUp(): void {
  parent::setUp();
  require_once __DIR__ . '/../../src/helpers.php';

  // ✅ CORRECT - Use envSetMultiple for multiple variables
  $this->envSetMultiple([
    'VORTEX_NOTIFY_PROJECT' => 'test-project',
    'VORTEX_NOTIFY_LABEL' => 'main',
    'VORTEX_NOTIFY_URL' => 'https://example.com',
  ]);
}

public function testCustomConfiguration(): void {
  // ✅ CORRECT - Use envSet for single variable
  $this->envSet('VORTEX_NOTIFY_CUSTOM', 'value');

  // ... test code ...
}

public function testFallbackVariables(): void {
  // ✅ CORRECT - Use envUnsetMultiple for unsetting multiple variables
  $this->envUnsetMultiple([
    'VORTEX_NOTIFY_SPECIFIC',
    'VORTEX_NOTIFY_ANOTHER',
  ]);

  // ✅ CORRECT - Set fallback variable
  $this->envSet('VORTEX_NOTIFY_GENERIC', 'fallback-value');

  // ... test code ...
}

public static function dataProviderHttpMethods(): array {
  return [
    'POST' => ['POST'],
    'GET' => ['GET'],
  ];
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

#### Request Mocking

The mock API mirrors the actual request function signatures for consistency and better ergonomics.

**Mock Methods**:

```php
// Mock request() - matches request() signature + $response parameter
mockRequest(
  string $deploy_webhook_url,
  array $options = [],
  array $response = [],
  string $namespace = 'DrevOps\\VortexTooling'
): void

// Mock request_get() - matches request_get() signature + $response parameter
mockRequestGet(
  string $deploy_webhook_url,
  array $headers = [],
  int $timeout = 10,
  array $response = [],
  string $namespace = 'DrevOps\\VortexTooling'
): void

// Mock request_post() - matches request_post() signature + $response parameter
mockRequestPost(
  string $deploy_webhook_url,
  $body = NULL,
  array $headers = [],
  int $timeout = 10,
  array $response = [],
  string $namespace = 'DrevOps\\VortexTooling'
): void
```

**Response Structure** (for all mock methods):
```php
[
  'ok' => true,                           // Optional: Success flag (default: TRUE)
  'status' => 200,                        // Required: HTTP status code
  'body' => 'response body',              // Optional: Response body (default: '')
  'error' => null,                        // Optional: cURL error (default: NULL)
  'info' => ['http_code' => 200],        // Optional: cURL info (default: [])
]
```

**Examples**:

```php
// GET request
$this->mockRequestGet(
  'https://api.example.com/data',
  [],
  10,
  ['status' => 200, 'body' => '{"id": 123}']
);

$result = request_get('https://api.example.com/data');
// Returns mocked response

// POST request
$this->mockRequestPost(
  'https://api.example.com/data',
  '{"data":"value"}',
  ['Content-Type: application/json'],
  10,
  ['status' => 201, 'body' => '{"id": 456}']
);

$result = request_post(
  'https://api.example.com/data',
  '{"data":"value"}',
  ['Content-Type: application/json']
);
// Returns mocked response

// Custom method (PUT, DELETE, etc.)
$this->mockRequest(
  'https://api.example.com/resource/123',
  ['method' => 'PUT'],
  ['status' => 200, 'body' => 'updated']
);

$result = request('https://api.example.com/resource/123', ['method' => 'PUT']);
// Returns mocked response
```

**Implementation Details**:

The request mock intercepts low-level curl functions used by the HTTP request functions:
- `curl_init()` - Stores URL, returns mock handle
- `curl_setopt_array()` - Extracts HTTP method from options
- `curl_exec()` - Validates URL/method, returns mocked body, increments index
- `curl_errno()` - Returns 0 or 1 based on error presence
- `curl_error()` - Returns mocked error message
- `curl_getinfo()` - Returns mocked info array with http_code

Note: `curl_close()` is no longer used (deprecated in PHP 8.0+). CurlHandle objects are automatically freed when they go out of scope.

### Resource Management

**Critical**: `request()` uses try-finally to ensure cleanup:

```php
function request(string $url, array $options = []): array {
  $ch = curl_init($url);

  try {
    // All curl operations here
    return [...];
  }
  finally {
    // CurlHandle objects are automatically freed when they go out of scope
    // (PHP 8.0+), so explicit curl_close() is no longer needed.
    // The unset here ensures the handle goes out of scope immediately.
    unset($ch);
  }
}
```

This ensures that:
- The CurlHandle is freed immediately in the finally block
- Mock index is incremented in `curl_exec()` before validation
- Exception tests properly mark mocks as consumed

## Test Naming Conventions

### Critical Pattern Rules

**Direct Tests** (testing functions directly):
```php
testMock[Function][Description][Outcome]
```

**Script Tests** (testing through fixture scripts):

For **single-function** mocks (passthru, quit):
```php
testMock[Function]Script[Description][Outcome]
```

For **multi-function** mocks (request with get/post/generic):
```php
testMock[Function][Method]Script[Description][Outcome]
```

### Examples

**Passthru Tests**:
- ✅ `testMockPassthruSuccess` (direct)
- ✅ `testMockPassthruScriptPassingSuccess` (script)
- ✅ `testMockPassthruScriptFailingSuccess` (script)

**Quit Tests**:
- ✅ `testMockQuit0Success` (direct, exit code in name)
- ✅ `testMockQuitScript0Success` (script, exit code in name)

**Request Tests**:
- ✅ `testMockRequestGetSuccess` (direct)
- ✅ `testMockRequestGetScriptPassingSuccess` (script - method before "Script")
- ✅ `testMockRequestPostScriptPassingSuccess` (script - method before "Script")
- ✅ `testMockRequestMultipleScriptSuccess` (script - testing multiple calls)

**Why method comes before "Script" for request**:
- Single function: `testMockPassthruScript...` (no ambiguity)
- Multiple functions: `testMockRequestGetScript...` (disambiguates which request function)

### Naming Convention Components

- **[Function]**: Passthru, Quit, Request, RequestGet, RequestPost, RequestMultiple
- **[Method]**: Get, Post (for request tests only)
- **[Description]**: Passing, Failing, Custom, Defaults, NetworkError, Multiple, MoreCalls, LessCalls, etc.
- **[Outcome]**: Success (test passes), Failure (test expects failure)

**Success vs Failure**:
- `Success` - Test pathway succeeds (expected behavior occurs)
- `Failure` - Test pathway fails (testing error conditions)

**Passing vs Failing** (fixtures only):
- `test-request-get-passing` - Fixture that expects successful execution
- `test-request-get-failing` - Fixture that expects failure execution

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

## Development Workflow

### Adding New Helper Functions

1. **Add to src/helpers.php**:
```php
/**
 * Function description.
 *
 * @param string $param
 *   Parameter description.
 *
 * @return mixed
 *   Return description.
 */
function my_new_function(string $param) {
  // Implementation
}
```

2. **Create unit test in tests/Unit/**:
```php
class MyNewFunctionTest extends UnitTestCase {
  public function testMyNewFunctionSuccess(): void {
    $result = \DrevOps\VortexTooling\my_new_function('input');
    $this->assertEquals('expected', $result);
  }
}
```

3. **Run tests**:
```bash
./vendor/bin/phpunit tests/Unit/MyNewFunctionTest.php
```

### Adding New Mock Functionality

1. **Add to MockTrait.php**:
```php
protected function mockMyFunction(array $response): void {
  $this->mockMyFunctionMultiple([$response]);
}

protected function mockMyFunctionMultiple(array $responses): void {
  // Store responses
  $this->mockMyFunctionResponses = array_merge(
    $this->mockMyFunctionResponses,
    $responses
  );

  // Create mock if not exists
  if ($this->mockMyFunction === NULL) {
    $this->mockMyFunction = $this->getFunctionMock('DrevOps\\VortexTooling', 'my_function');
    $this->mockMyFunction
      ->expects($this->any())
      ->willReturnCallback(function () {
        // Mock implementation
      });
  }
}

protected function mockMyFunctionAssertAllMocksConsumed(): void {
  // Teardown validation
}
```

2. **Add to mockTearDown()**:
```php
protected function mockTearDown(): void {
  // ... existing teardown ...

  // Add new mock teardown
  $this->mockMyFunctionAssertAllMocksConsumed();
  $this->mockMyFunction = NULL;
  $this->mockMyFunctionResponses = [];
  $this->mockMyFunctionIndex = 0;
  $this->mockMyFunctionChecked = FALSE;
}
```

3. **Create self-tests in tests/Self/**:
- Direct tests (14+ tests)
- Script tests (12+ tests)
- Follow naming conventions

4. **Create fixtures in tests/Fixtures/**:
- Passing fixture
- Failing fixture
- Multiple calls fixture (if applicable)

### Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Specific test suite
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Self/

# Specific test file
./vendor/bin/phpunit tests/Self/MockRequestSelfTest.php

# With testdox output
./vendor/bin/phpunit --testdox

# With coverage
./vendor/bin/phpunit --coverage-html coverage/
```

## Common Patterns

### Multiple Mock Calls

```php
// Queue multiple responses - just call mock methods multiple times
$this->mockRequestGet(
  'https://example.com/first',
  [],
  10,
  ['status' => 200, 'body' => 'first']
);

$this->mockRequestGet(
  'https://example.com/second',
  [],
  10,
  ['status' => 200, 'body' => 'second']
);

// Both calls return mocked responses in order
$result1 = request_get('https://example.com/first');
$result2 = request_get('https://example.com/second');
```

### Testing Too Many Calls

```php
// Mock only 1 response
$this->mockRequestGet(
  'https://example.com/api',
  [],
  10,
  ['status' => 200]
);

// Expect exception on second call
$this->expectException(\RuntimeException::class);
$this->expectExceptionMessage('curl_init() called more times than mocked responses. Expected 1 request(s), but attempting request #2');

request_get('https://example.com/api');  // OK
request_get('https://example.com/api');  // Throws RuntimeException
```

### Testing Too Few Calls

```php
// Mock 3 responses but only make 2 calls
$this->mockRequestGet('https://example.com/1', [], 10, ['status' => 200]);
$this->mockRequestGet('https://example.com/2', [], 10, ['status' => 200]);
$this->mockRequestGet('https://example.com/3', [], 10, ['status' => 200]);

request_get('https://example.com/1');
request_get('https://example.com/2');

// Expect assertion failure in tearDown
$this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
$this->expectExceptionMessage('Not all mocked request responses were consumed. Expected 3 request(s), but only 2 request(s) were made.');

// Manually trigger teardown check
$this->mockRequestAssertAllMocksConsumed();
```

### Testing URL Mismatch

```php
// Mock expects different URL
$this->mockRequestGet(
  'https://expected.com/api',
  [],
  10,
  ['status' => 200]
);

$this->expectException(\RuntimeException::class);
$this->expectExceptionMessage('request made to unexpected URL. Expected "https://expected.com/api", got "https://actual.com/api".');

request_get('https://actual.com/api');  // URL mismatch
```

### Script Testing with runScript()

```php
// Mock the function
$this->mockRequestGet(
  'https://example.com/api',
  [],
  10,
  ['status' => 200, 'body' => 'success']
);

// Run fixture script
$output = $this->runScript('test-request-get-passing', 'tests/Fixtures');

// Assert on script output
$this->assertStringContainsString('Response status: 200', $output);
$this->assertStringContainsString('Response ok: true', $output);
$this->assertStringContainsString('Response body: success', $output);
```

## Debugging

### Enable Debug Output

```bash
# In tests
TEST_VORTEX_DEBUG=1 ./vendor/bin/phpunit

# In scripts
VORTEX_DEBUG=1 php script.php
```

### Common Issues

**Issue**: "Function already enabled" error

**Cause**: Trying to create multiple mocks for the same function

**Solution**: The mock methods automatically handle queueing, just call them multiple times:
```php
// ✅ Correct - each call queues a response
$this->mockRequestGet('https://example.com/api', [], 10, ['status' => 200]);  // Creates mock + queues first response
$this->mockRequestGet('https://example.com/api2', [], 10, ['status' => 200]); // Queues second response (mock already exists)
```

**Issue**: "Not all mocked responses were consumed"

**Cause**: Mocked more responses than actual function calls

**Solution**: Ensure test makes all expected function calls

**Issue**: Test hangs waiting for input

**Cause**: Script requires interactive input and wasn't mocked

**Solution**: Mock the interactive function call

## Best Practices

### DO

- ✅ Use queue-based mocking for sequential calls
- ✅ Validate all required fields in mock responses
- ✅ Test both success and error pathways
- ✅ Use descriptive test names following conventions
- ✅ Create fixtures for integration testing
- ✅ Always validate that all mocks are consumed
- ✅ Use try-finally for resource cleanup
- ✅ Test too many calls, too few calls, and mismatches

### DON'T

- ❌ Create new mocks for each call (use queue pattern)
- ❌ Skip teardown validation
- ❌ Mix direct and script tests in same method
- ❌ Forget to load helpers.php in setUp()
- ❌ Use inconsistent naming conventions
- ❌ Create fixtures without proper namespace
- ❌ Test without asserting on output

## Reference

### PHPUnit Attributes

```php
#[CoversClass(UnitTestCase::class)]  // Coverage declaration
```

### UnitTestCase Methods

```php
protected function setUp(): void                           // Setup before each test
protected function tearDown(): void                        // Cleanup after each test
protected function runScript(string $name, string $dir)   // Run fixture script
```

### MockTrait Methods

```php
// Passthru
protected function mockPassthru(array $response): void
protected function mockPassthruMultiple(array $responses): void
protected function mockPassthruAssertAllMocksConsumed(): void

// Quit
protected function mockQuit(int $code = 0): void

// Request
protected function mockRequest(string $url, array $options = [], array $response = [], string $namespace = 'DrevOps\\VortexTooling'): void
protected function mockRequestGet(string $url, array $headers = [], int $timeout = 10, array $response = [], string $namespace = 'DrevOps\\VortexTooling'): void
protected function mockRequestPost(string $url, $body = NULL, array $headers = [], int $timeout = 10, array $response = [], string $namespace = 'DrevOps\\VortexTooling'): void
protected function mockRequestMultiple(array $responses, string $namespace = 'DrevOps\\VortexTooling'): void
protected function mockRequestAssertAllMocksConsumed(): void

// Teardown
protected function mockTearDown(): void
```

## Additional Resources

- PHPUnit Documentation: https://phpunit.de/documentation.html
- php-mock Documentation: https://github.com/php-mock/php-mock-phpunit
- Vortex Template: https://github.com/drevops/vortex

---

*This documentation should be updated whenever significant changes are made to the tooling package structure, mock system, or testing conventions.*
