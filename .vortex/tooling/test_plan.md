# Plan: Comprehensive Request Function Testing

## 1. Mock Implementation (in MockTrait.php)

**Add mock methods:**
- `mockRequest(array $response)` - Mock single request call
- `mockRequestMultiple(array $responses)` - Mock multiple requests
- `mockRequestAssertAllMocksConsumed()` - Verify all mocks consumed

**Mock response structure:**
```php
[
  'url' => 'https://example.com/api',      // Expected URL
  'method' => 'GET',                        // Optional: expected method
  'response' => [
    'ok' => true,
    'status' => 200,
    'body' => 'response body',
    'error' => null,
    'info' => ['http_code' => 200]
  ]
]
```

**Implementation approach:** Mock the low-level curl_* functions (curl_init, curl_exec, curl_getinfo, etc.) used by the HTTP request functions to intercept all request operations.

## 2. Test Class Structure (tests/Self/MockRequestSelfTest.php)

**Direct Tests (14 tests):**

1. **testMockRequestGetSuccess** - Basic GET with 200
   - Mock: url, status 200, body "success"
   - Assert: ok=true, status=200, body contains "success"

2. **testMockRequestGetCustomSuccess** - GET with custom values
   - Mock: status 201, custom body, custom info
   - Assert: all custom values returned correctly

3. **testMockRequestGetDefaultsSuccess** - GET with minimal mock
   - Mock: only url and status
   - Assert: defaults filled in correctly

4. **testMockRequestGetFailure404** - GET with 404 error
   - Mock: status 404, ok=false
   - Assert: ok=false, status=404

5. **testMockRequestGetFailure500** - GET with 500 error
   - Mock: status 500, ok=false
   - Assert: ok=false, status=500

6. **testMockRequestGetNetworkError** - GET with network error
   - Mock: error="Could not resolve host"
   - Assert: error message present, ok=false

7. **testMockRequestPostSuccess** - POST with body
   - Mock: POST method, body present, status 201
   - Assert: ok=true, status=201

8. **testMockRequestPostJsonSuccess** - POST with JSON body
   - Mock: POST with JSON content-type header
   - Assert: request handled correctly

9. **testMockRequestCustomMethodSuccess** - PUT/DELETE
   - Mock: method=PUT, status 200
   - Assert: custom method works

10. **testMockRequestMultipleSuccess** - Sequential calls
    - Mock: 3 different responses
    - Assert: each returns correct response in order

11. **testMockRequestMultipleMoreCallsFailure** - Too many calls
    - Mock: 2 responses
    - Make: 3 calls
    - Assert: RuntimeException on 3rd call

12. **testMockRequestMultipleLessCallsFailure** - Too few calls
    - Mock: 3 responses
    - Make: 2 calls
    - Assert: AssertionFailedError in tearDown

13. **testMockRequestFailureArgumentExceptionUrl** - Missing URL validation
    - Mock: response without url key
    - Assert: InvalidArgumentException

14. **testMockRequestFailureAssertUnexpectedUrl** - URL mismatch
    - Mock: url="https://example.com"
    - Call: url="https://different.com"
    - Assert: RuntimeException with clear message

**Script Tests (12 tests):**

15. **testMockRequestGetScriptPassingSuccess** - GET through script (uses test-request-get-passing)
16. **testMockRequestGetScriptFailingSuccess** - GET 404 through script (uses test-request-get-failing)
17. **testMockRequestGetScriptCustomSuccess** - Custom values through script (uses test-request-get-passing)
18. **testMockRequestGetScriptDefaultsSuccess** - Defaults through script (uses test-request-get-passing)
19. **testMockRequestGetScriptNetworkErrorSuccess** - Network error through script (uses test-request-get-failing)
20. **testMockRequestPostScriptPassingSuccess** - POST success through script (uses test-request-post-passing)
21. **testMockRequestPostScriptFailingSuccess** - POST failure through script (uses test-request-post-failing)
22. **testMockRequestMultipleScriptSuccess** - Multiple calls through script
23. **testMockRequestMultipleScriptMoreCallsFailure** - Too many calls through script
24. **testMockRequestMultipleScriptLessCallsFailure** - Too few calls through script
25. **testMockRequestGetScriptFailureArgumentExceptionUrl** - Missing URL through script
26. **testMockRequestGetScriptFailureAssertUnexpectedUrl** - URL mismatch through script

**Naming Convention:**
- **Test method names**: Use `Success`/`Failure` to indicate the test pathway (what behavior is being tested)
- **Fixture names**: Use `passing`/`failing` to describe the fixture behavior (whether it passes or fails)
- **Request script test naming**: For request tests with multiple methods (get, post, request), the pattern is `testMockRequest[Method]Script[Description][Outcome]` (e.g., `testMockRequestGetScriptPassingSuccess`, `testMockRequestPostScriptFailingSuccess`)

## 3. Test Fixtures (tests/Fixtures/)

**test-request-get-passing:**
```php
#!/usr/bin/env php
<?php
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

**test-request-get-failing:**
```php
#!/usr/bin/env php
<?php
declare(strict_types=1);
namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../../src/helpers.php';

echo 'Script will call request_get expecting failure' . PHP_EOL;

$result = request_get('https://example.com/not-found');

echo 'Response status: ' . $result['status'] . PHP_EOL;
echo 'Response ok: ' . ($result['ok'] ? 'true' : 'false') . PHP_EOL;

if (!$result['ok']) {
  echo 'Request failed as expected' . PHP_EOL;
}
```

**test-request-post-passing:**
```php
#!/usr/bin/env php
<?php
declare(strict_types=1);
namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../../src/helpers.php';

echo 'Script will call request_post' . PHP_EOL;

$result = request_post('https://example.com/api', json_encode(['key' => 'value']), ['Content-Type: application/json']);

echo 'Response status: ' . $result['status'] . PHP_EOL;
echo 'Response ok: ' . ($result['ok'] ? 'true' : 'false') . PHP_EOL;

if ($result['ok']) {
  echo 'POST succeeded' . PHP_EOL;
}
```

**test-request-post-failing:**
```php
#!/usr/bin/env php
<?php
declare(strict_types=1);
namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../../src/helpers.php';

echo 'Script will call request_post expecting failure' . PHP_EOL;

$result = request_post('https://example.com/error', json_encode(['key' => 'value']), ['Content-Type: application/json']);

echo 'Response status: ' . $result['status'] . PHP_EOL;
echo 'Response ok: ' . ($result['ok'] ? 'true' : 'false') . PHP_EOL;

if (!$result['ok']) {
  echo 'POST failed as expected' . PHP_EOL;
}
```

**test-request-multiple:**
```php
#!/usr/bin/env php
<?php
declare(strict_types=1);
namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../../src/helpers.php';

echo 'Script will call request functions multiple times' . PHP_EOL;

$result1 = request_get('https://example.com/first');
echo 'First call status: ' . $result1['status'] . PHP_EOL;

$result2 = request_post('https://example.com/second', 'data');
echo 'Second call status: ' . $result2['status'] . PHP_EOL;

$result3 = request('https://example.com/third', ['method' => 'PUT']);
echo 'Third call status: ' . $result3['status'] . PHP_EOL;

echo 'Script completed' . PHP_EOL;
```

## 4. Implementation Order

1. **Step 1:** Implement `mockRequest*` methods in MockTrait.php
2. **Step 2:** Create test fixtures (5 files: get-passing, get-failing, post-passing, post-failing, multiple)
3. **Step 3:** Implement direct tests (14 tests)
4. **Step 4:** Implement script tests (12 tests)
5. **Step 5:** Run all Self tests to verify no conflicts

## 5. Key Patterns to Follow

- **Same as passthru:** Queue-based response system with index tracking
- **Validation:** Require 'url' key in mock response (like 'cmd' for passthru)
- **Teardown:** Auto-verify all mocks consumed
- **Error messages:** Clear, descriptive messages for mismatches
- **No process isolation:** Should work without it (like passthru)

**Total:** 26 tests covering positive/negative scenarios, direct/script execution, single/multiple calls, and all three request methods (get, post, request).
