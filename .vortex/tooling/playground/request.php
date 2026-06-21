#!/usr/bin/env php
<?php

/**
 * @file
 * Manual demo of HTTP request functions.
 *
 * Usage: ./playground/request.php.
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../src/helpers.php';

echo "=== HTTP Request Demo ===\n\n";

// Test 1: Simple GET request.
echo "Test 1: GET request to httpbin.org/get\n";
$result = request_get('https://httpbin.org/get');
echo "  Status: " . $result['status'] . "\n";
echo "  OK: " . ($result['ok'] ? 'true' : 'false') . "\n";
echo "  Body preview: " . substr((string) $result['body'], 0, 100) . "...\n";
echo "  Result: " . ($result['ok'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";
echo "\n";

// Test 2: GET with headers.
echo "Test 2: GET request with custom headers\n";
$result = request_get(
  'https://httpbin.org/headers',
  ['X-Custom-Header: TestValue', 'X-Another-Header: AnotherValue']
);
echo "  Status: " . $result['status'] . "\n";
echo "  OK: " . ($result['ok'] ? 'true' : 'false') . "\n";
if ($result['ok']) {
  echo "  Check response body for custom headers:\n";
  echo "  " . substr((string) $result['body'], 0, 200) . "...\n";
}
echo "  Result: " . ($result['ok'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";
echo "\n";

// Test 3: POST request with JSON.
echo "Test 3: POST request with JSON body\n";
$result = request_post(
  'https://httpbin.org/post',
  '{"test": "data", "number": 123, "array": [1, 2, 3]}',
  ['Content-Type: application/json']
);
echo "  Status: " . $result['status'] . "\n";
echo "  OK: " . ($result['ok'] ? 'true' : 'false') . "\n";
if ($result['ok']) {
  echo "  Response preview:\n";
  echo "  " . substr((string) $result['body'], 0, 200) . "...\n";
}
echo "  Result: " . ($result['ok'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";
echo "\n";

// Test 4: Testing error handling (404).
echo "Test 4: Testing error handling (404)\n";
$result = request_get('https://httpbin.org/status/404');
echo "  Status: " . $result['status'] . "\n";
echo "  OK: " . ($result['ok'] ? 'true' : 'false') . "\n";
$expected_fail = !$result['ok'];
echo "  Result: " . ($expected_fail ? "✓ SUCCESS (404 correctly detected as error)" : "✗ FAILED (404 should be detected as error)") . "\n";
echo "\n";

// Test 5: Testing 500 error.
echo "Test 5: Testing server error (500)\n";
$result = request_get('https://httpbin.org/status/500');
echo "  Status: " . $result['status'] . "\n";
echo "  OK: " . ($result['ok'] ? 'true' : 'false') . "\n";
$expected_fail = !$result['ok'];
echo "  Result: " . ($expected_fail ? "✓ SUCCESS (500 correctly detected as error)" : "✗ FAILED (500 should be detected as error)") . "\n";
echo "\n";

// Test 6: Custom method (PUT).
echo "Test 6: Custom method (PUT) using request()\n";
$result = request(
  'https://httpbin.org/put',
  [
    'method' => 'PUT',
    'body' => '{"updated": true, "timestamp": 1234567890}',
    'headers' => ['Content-Type: application/json'],
  ]
);
echo "  Status: " . $result['status'] . "\n";
echo "  OK: " . ($result['ok'] ? 'true' : 'false') . "\n";
echo "  Result: " . ($result['ok'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";
echo "\n";

// Test 7: DELETE method.
echo "Test 7: Custom method (DELETE) using request()\n";
$result = request(
  'https://httpbin.org/delete',
  ['method' => 'DELETE']
);
echo "  Status: " . $result['status'] . "\n";
echo "  OK: " . ($result['ok'] ? 'true' : 'false') . "\n";
echo "  Result: " . ($result['ok'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";
echo "\n";

// Test 8: Timeout handling.
echo "Test 8: Testing timeout (3 second delay with 1 second timeout)\n";
echo "  This should trigger a timeout error...\n";
$result = request_get('https://httpbin.org/delay/3', [], 1);
if (!empty($result['error'])) {
  echo "  Error: " . $result['error'] . "\n";
  echo "  Result: ✓ SUCCESS (Timeout correctly detected)\n";
}
else {
  echo "  Result: ✗ FAILED (Timeout not detected)\n";
}
echo "\n";

// Test 9: Successful request with custom timeout.
echo "Test 9: Successful request with longer timeout\n";
$result = request_get('https://httpbin.org/delay/1', [], 5);
echo "  Status: " . $result['status'] . "\n";
echo "  OK: " . ($result['ok'] ? 'true' : 'false') . "\n";
echo "  Result: " . ($result['ok'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";

echo "\n=== Demo Complete ===\n";
